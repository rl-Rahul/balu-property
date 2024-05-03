<?php


namespace App\Service;

use App\Entity\CompanySubscriptionPlan;
use App\Entity\Payment;
use App\Entity\PaymentLog;
use App\Entity\Property;
use App\Entity\StripeEvent;
use App\Entity\SubscriptionHistory;
use App\Entity\SubscriptionPlan;
use App\Entity\UserIdentity;
use Doctrine\Persistence\ManagerRegistry;
use Google\Service\CloudSearch\UserId;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\RateLimitException;
use Stripe\PaymentLink;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\BadMethodCallException;
use Symfony\Contracts\Translation\TranslatorInterface;
use ReceiptValidator\iTunes\Validator as iTunesValidator;
use App\Entity\Role;
use App\Entity\Apartment;
use Twilio\TwiML\Voice\Pay;

/**
 * PaymentService
 *
 * Payment service actions.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class PaymentService extends BaseService
{
    /**
     * @var ManagerRegistry $doctrine
     */
    private ManagerRegistry $doctrine;

    /**
     * @var ObjectService $objectService
     */
    private ObjectService $objectService;

    /**
     * @var PropertyService $propertyService
     */
    private PropertyService $propertyService;

    /**
     * @var TranslatorInterface $translator
     */
    private TranslatorInterface $translator;

    /**
     * @var ParameterBagInterface $params
     */
    private ParameterBagInterface $params;

    /**
     * @var CompanyService $companyService
     */
    private CompanyService $companyService;

    /**
     * PaymentService constructor.
     *
     * @param ManagerRegistry $doctrine
     * @param ObjectService $objectService
     * @param PropertyService $propertyService
     * @param TranslatorInterface $translator
     * @param ParameterBagInterface $params
     * @param CompanyService $companyService
     */
    public function __construct(ManagerRegistry $doctrine, ObjectService $objectService, PropertyService $propertyService,
                                TranslatorInterface $translator, ParameterBagInterface $params, CompanyService $companyService)
    {
        $this->doctrine = $doctrine;
        $this->objectService = $objectService;
        $this->propertyService = $propertyService;
        $this->translator = $translator;
        $this->params = $params;
        $this->companyService = $companyService;
    }

    /**
     * Save payment details
     *
     * @param \Stripe\Subscription $subscription
     * @param float $amount
     * @param bool $isSuccess
     * @param string $response
     * @param string|null $role
     * @param Property|null $property
     * @param bool $isCompany
     * @param UserIdentity|null $companyUser
     * @param UserIdentity|null $user
     * @return void
     * @throws \Exception
     */
    private function savePaymentDetails(\Stripe\Subscription $subscription, float $amount, bool $isSuccess, string $response, ?string $role,
                                        ?Property $property = null, bool $isCompany = false, ?UserIdentity $companyUser = null,
                                        ?UserIdentity $user = null): void
    {
        $em = $this->doctrine->getManager();
        $payment = new Payment();
        if (!($user instanceof UserIdentity)) {
            $user = (!(is_null($property))) ? $property->getUser() : $companyUser;
        }
        $role = $em->getRepository(Role::class)->findOneBy(['publicId' => $role]);
        $payment->setUser($user);
        $payment->setResponse($response);
        $payment->setIsSuccess($isSuccess);
        $payment->setTransactionId($subscription['id']);
        $payment->setAmount($amount);
        $payment->setIsCompany($isCompany);
        $payment->setRole($role);
        $payment->setEvent($subscription->id);
        if ($isCompany == true && $user->getCompanySubscriptionPlan() instanceof CompanySubscriptionPlan) {
            $payment->setCompanyPlan($user->getCompanySubscriptionPlan());
            $payment->setPeriod($user->getCompanySubscriptionPlan()->getPeriod());
            $payment->setSelectedItems($this->companyService->getCompanyUsers($user));
        } else if ($property->getSubscriptionPlan() instanceof SubscriptionPlan) {
            $payment->setSubscriptionPlan($property->getSubscriptionPlan());
            $payment->setPeriod($property->getSubscriptionPlan()->getPeriod());
            $payment->setSelectedItems($this->propertyService->getApartments($property));
        }
        if (!(is_null($property))) {
            $payment->setProperty($property);
        }
        $startDate = new \DateTime('now');
        $payment->setStartDate($startDate->setTimestamp($subscription['current_period_start']));
        $endDate = new \DateTime('now');
        $payment->setEndDate($endDate->setTimestamp($subscription['current_period_end']));
        $em->persist($payment);
        $em->flush();
    }

    /**
     * invoicePaymentSucceed
     *
     * @param array $eventData
     * @param UserIdentity $user
     * @throws \Exception
     */
    public function invoicePaymentSucceed(array $eventData, UserIdentity $user): void
    {
        $em = $this->doctrine->getManager();
        $stripeEvent = new StripeEvent();
        $stripeEvent->setEventId($eventData['id']);
        $em->persist($stripeEvent);
        $property = $company = null;
        $isCompany = false;
        $subscription = $eventData['data']['object']['id'];
        $metaData = $eventData['data']['object']['lines']['data'][0]['metadata'];
        if (empty(array_intersect(array_flip($metaData), ['company', 'property']))) {
            throw new InvalidArgumentException('inValidArgument', 400);
        }
        $role = $metaData['role'];
        $period = $eventData['data']['object']['lines']['data'][0]['period'];
        if (array_key_exists('company', $metaData)) {
            $company = $em->getRepository(UserIdentity::class)
                ->findOneBy(['publicId' => $metaData['company']]);
            if ($company instanceof UserIdentity) {
                $date = $company->getExpiryDate() ? $company->getExpiryDate() : new \DateTime();
//                $plan = $em->getRepository(CompanySubscriptionPlan::class)->findOneBy(['publicId' => $eventData['data']['object']['lines']['data'][0]['period']]);
//                $modify = $plan->getPeriod() === 30 ? '+1 month' : '+1 year';
//                $endDate = $date->modify($modify)->getTimestamp();
                $planEndDate = $date->setTimestamp($period['end']);
                $company->setPlanEndDate($planEndDate);
                $company->setExpiryDate(null);
                $em->flush();
            }
            $isCompany = true;
        }
        if (array_key_exists('property', $metaData)) {
            $property = $em->getRepository(Property::class)
                ->findOneBy(['publicId' => $metaData['property']]);
            if ($property instanceof Property) {
                $date = $property->getPlanEndDate() ? $property->getPlanEndDate() : new \DateTime();
//                $plan = $em->getRepository(SubscriptionPlan::class)->findOneBy(['publicId' => $eventData->data->object->metadata->plan]);
//                $modify = $plan->getPeriod() === 30 ? '+1 month' : '+1 year';
//                $endDate = $date->modify($modify)->getTimestamp();
//                $planEndDate = $date->setTimestamp($endDate);
                $property->setActive(true);
                //set objects active
                $em->getRepository(Apartment::class)->setObjectStatus($property, true);
                $property->setPlanEndDate($date->setTimestamp($period['end']));
                $property->setPendingPayment(false);
                $em->flush();
            }
        }
        $amount = $eventData['data']['object']['lines']['data'][0]['plan']['amount'];
        $total = $eventData['data']['object']['total'];
        $amount = ($total == 0) ? $amount : $total;
        $finalAmount = $amount / 100;
        $this->savePaymentDetails($subscription, $finalAmount, true,
            $eventData['data']['object']['billing_reason'], $role, $property, $isCompany, $company, $user);
        $em->flush();
    }

    /**
     * customerSubscriptionUpdate
     *
     * @param object $eventData
     * @param UserIdentity $user
     */
    public function customerSubscriptionUpdate(object $eventData, UserIdentity $user): void
    {
        $em = $this->doctrine->getManager();
        $event = new StripeEvent();
        $event->setEventId($eventData['id']);
        $em->persist($event);
        $metaData = $eventData['data']['object']['lines']['data'][0]['metadata'];
        if (array_key_exists('property', $metaData)) {
            $propertyId = $metaData['property'];
            $role = $em->getRepository(Role::class)
                ->findOneBy(['roleKey' => $metaData['role']]);
            $property = $em->getRepository(Property::class)->findOneBy(['publicId' => $propertyId]);
            if ($property instanceof Property) {
                $currentDate = new \DateTime();
                $currentPlanEndDate = $currentDate->setTimestamp($eventData['data']['object']['current_period_end']);
                $property->setPlanEndDate($currentPlanEndDate);
                $subscription = $eventData['data']['object']['id'];
                $this->savePaymentDetails($subscription, 0, true, $eventData['type'], $role->getRoleKey(), $property, false, null, $user);
            }
            $em->flush();
        }
    }

    /**
     * recurringPayment
     *
     * @param SubscriptionPlan $subscriptionPlan
     * @param Property $property
     * @param bool $planChange
     * @param int $period
     * @param UserIdentity $user
     * @param string $role
     * @param \Stripe\Customer|null $customer
     * @param StripeClient|null $stripeClient
     * @return array
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function recurring(SubscriptionPlan $subscriptionPlan, Property $property, bool $planChange, int $period,
                              UserIdentity $user, string $role, \Stripe\Customer $customer = null, ?StripeClient $stripeClient = null): array
    {
        $data = null;
        $isSuccess = false;
        $curDate = new \DateTime();
        $locale = $user->getLanguage();
        $expiringOn = $curDate;
        if ((is_null($customer)) && ($planChange) && ($property->getRecurring())) {
            $subscription = $stripeClient->subscriptions->retrieve($property->getStripeSubscription());
            $planDetails = $this->objectService->planDetails($property, $period);
            $items = [
                [
                    'id' => $subscription->items->data[0]->id,
                    'plan' => $planDetails['plan']->getStripePlan() // Switch to new plan
                ]
            ];
            $return = $stripeClient->subscriptions->update($property->getStripeSubscription(), [
                'cancel_at_period_end' => false,
                'items' => [
                    [
                        'id' => $subscription->items->data[0]->id,
                        'plan' => $planDetails['plan']->getStripePlan()
                    ]
                ]
            ]);

            if ($period === $property->getSubscriptionPlan()->getPeriod()) {
                $invoice = $stripeClient->invoices->upcoming([
                    'customer' => $subscription->customer,
                    'subscription' => $property->getStripeSubscription(),
                    'subscription_items' => $items,
                    'subscription_proration_date' => $curDate->getTimestamp()
                ]);

                // New Plan Invoice of upcoming payment
                $amount = $invoice->total;
                $expiringOn->setTimestamp($invoice->period_end);
                $property->setPendingPayment(true);
                $property->setSubscriptionPlan($planDetails['plan']);
            } else {
                $invoice = $stripeClient->invoices->all([
                    "limit" => 2
                ]);
                $amount = $invoice->data[0]->total;
                $expiringOn->setTimestamp($invoice->data[0]->period_end);
                $property->setSubscriptionPlan($planDetails['plan']);
            }
            $subscriptionPlan = $planDetails['plan'];
        } else {
            $planEndDate = $property->getPlanEndDate();
            $expiringDays = $curDate->diff($planEndDate)->format('%r%a');
            $trialPeriod = ($expiringDays > 0) ? abs($expiringDays) : 0;
//            if ($_SERVER['REMOTE_ADDR'] === '103.107.143.98') {
//                dd($subscriptionPlan->getStripePlan());
//            }
            $return = $stripeClient->subscriptions->create(array(
                "customer" => $customer->id,
                "items" => array(
                    array(
                        "plan" => $subscriptionPlan->getStripePlan()
                    )
                ),
                'trial_period_days' => $trialPeriod,
                "metadata" => array(
                    "property" => $property->getPublicId(),
                    "user" => $user->getPublicId(),
                    "role" => $role
                )
            ));
            $amount = $subscriptionPlan->getAmount() * 100;
            $expiringOn->setTimestamp($return->current_period_end);
        }
        if (($return->status === "active") || ($return->status === "trialing")) {
            $msg = ($return->status === "trialing") ? 'paymentTrailing' : 'paymentSuccessStatus';
            $data['transactionId'] = $return->id;
            $data['amount'] = number_format((float)($amount) / 100, 2, '.', '');
            $data['status'] = $this->translator->trans($msg, array(), null, $locale);
            $this->propertyService->updatePropertyExpiry($property, $subscriptionPlan, $expiringOn, true);
            $property->setStripeSubscription($return->id);
            $isSuccess = true;
        }
        $amount = isset($data['amount']) ? $data['amount'] : number_format((float)($amount) / 100, 2, '.', '');
        $this->savePaymentDetails($return->id, $amount, $isSuccess, $return->status, $role, $property);
        $date = new \DateTime();
        $data['date'] = $date->setTimestamp($return->created);
        $data['address'] = $property->getAddress();
        $data['period'] = $subscriptionPlan->getPeriod();

        return $data;
    }

    /**
     * singlePayment
     *
     * @param \Stripe\Customer $customer
     * @param Property $property
     * @param bool $planChange
     * @param int $period
     * @param UserIdentity $user
     * @param string $role
     * @param StripeClient|null $stripeClient
     * @param SubscriptionPlan|null $subscriptionPlan
     * @return array /array
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function singlePayment(\Stripe\Customer $customer, Property $property, bool $planChange,
                                  int $period, UserIdentity $user, string $role, ?StripeClient $stripeClient, SubscriptionPlan $subscriptionPlan = null): array
    {
        $data = null;
        $isSuccess = false;
        $amount = (!(is_null($subscriptionPlan))) ? $subscriptionPlan->getAmount() : 0;
//        if ($propertyAmount !== (float) $amount) {
//            throw new RateLimitException('rateMismatch');
//        }
        if ($planChange) {
            if ($period === $property->getSubscriptionPlan()->getPeriod())
                $planDetails = $this->objectService->planDetails($property, $property->getSubscriptionPlan()
                    ->getPeriod());
            else
                $planDetails = $this->objectService->planDetails($property, $period, false);
            $amount = $planDetails['amountToBePayed'];
            $subscriptionPlan = $planDetails['plan'];
        }
        $locale = $user->getLanguage();
        $data['amount'] = number_format((float)$amount, 2, '.', '');
        $data['status'] = $this->translator->trans('paymentFailedStatus', array(), null, $locale);
        $stripeClient->paymentLinks->create(array());
        $finalAmount = $amount * 100;
        $return = $stripeClient->charges->create(array(
            "customer" => $customer->id,
            "amount" => $finalAmount,
            "currency" => $this->params->get('default_currency'),
            "receipt_email" => $customer->email,
            "metadata" => array(
                "property" => $property->getPublicId(),
                "user" => $user->getPublicId(),
                "plan" => $subscriptionPlan->getPublicId(),
                "role" => $role
            )
        ));
        if ($return->status === "succeeded") {
            $data['transactionId'] = $return->id;
            $data['amount'] = number_format((float)$return->amount / 100, 2, '.', '');
            $data['status'] = $this->translator->trans('paymentSuccessStatus', array(), null, $locale);
            $this->propertyService->updatePropertyExpiry($property, $subscriptionPlan);
            $isSuccess = true;
        }
        $amount = isset($data['amount']) ? $data['amount'] : number_format((float)($finalAmount) / 100, 2, '.', '');
        $this->savePaymentDetails($return->id, $amount, $isSuccess, $return->status, $role, $property);
        $date = new \DateTime();
        $data['date'] = $date->setTimestamp($return->created);
        $data['address'] = $property->getAddress();
        $data['period'] = $subscriptionPlan->getPeriod();

        return $data;
    }

    /**
     * Validate ios inapp payment receipt.
     *
     * @param string $receiptBase64Data
     * @return array or throws Exception.
     * @throws
     */
    public function validateReceipt(string $receiptBase64Data): array
    {
        $validator = new iTunesValidator(iTunesValidator::ENDPOINT_PRODUCTION);
        $response = $validator->setReceiptData($receiptBase64Data)->validate();
        if ($response->isValid()) {
            return $response->getReceipt();
        } else {
            throw new InvalidArgumentException('invalidReceipt', $response->getResultCode());
        }
    }

    /**
     * Get transaction details.
     *
     * @param array $receiptResponse
     * @param string $transactionId
     * @return array
     */
    public function getInAppTransactionDetails(array $receiptResponse, string $transactionId): array
    {
        $transactionDetails = [];
        $inAppData = $receiptResponse['in_app'];
        $transactionDataKey = array_search($transactionId, array_column($inAppData, 'transaction_id'));
        if ($transactionDataKey) {
            $transactionDetails = $inAppData[$transactionDataKey];
        }
        return $transactionDetails;
    }

    /**
     * Update the property plan when a payment is successful
     *
     * @param SubscriptionPlan $subscriptionPlan
     * @param Property $property
     * @param float $transactionAmount
     * @param UserIdentity $user
     * @param array $receiptResponse
     * @param string $transactionId
     * @param string $role
     * @return array
     * @throws \Exception
     */
    public function updatePropertySubscription(SubscriptionPlan $subscriptionPlan, Property $property, float $transactionAmount, UserIdentity $user, array $receiptResponse, string $transactionId, string $role): array
    {
        $data = null;
        $transactionDetails = $this->getInappTransactionDetails($receiptResponse, $transactionId);
        $amount = (!(is_null($subscriptionPlan))) ? $subscriptionPlan->getInappAmount() : 0;
        if ($transactionAmount !== (float)$amount) {
            throw new \Exception('transactionAmountMismatch');
        }
        $locale = $user->getLanguage();
        $data['amount'] = number_format((float)$amount, 2, '.', '');
        $data['status'] = $this->translator->trans('paymentFailedStatus', array(), null, $locale);

        $data['transactionId'] = $transactionId;
        $data['amount'] = number_format((float)$transactionAmount, 2, '.', '');
        $data['status'] = $this->translator->trans('paymentSuccessStatus', array(), null, $locale);
        $this->propertyService->updatePropertyExpiry($property, $subscriptionPlan);
        $isSuccess = true;

        $this->savePaymentDetails($transactionId, $amount, $isSuccess, 'Inapp Property Payment success', $role, $property);
        $date = new \DateTime();
        $transactionTime = array_key_exists('original_purchase_date_ms', $transactionDetails) ? $transactionDetails['original_purchase_date_ms'] / 1000 : $date->getTimestamp();
        $data['date'] = $date->setTimestamp($transactionTime);
        $data['address'] = $property->getAddress();
        $data['period'] = $subscriptionPlan->getPeriod();

        return $data;
    }

    /**
     * @param UserIdentity $user
     * @param Request $request
     * @param array $receiptResponse
     * @param string $transactionId
     * @param string $role
     * @return array
     * @throws \Exception
     */
    public function updateCompanySubscription(UserIdentity $user, Request $request, array $receiptResponse, string $transactionId, string $role): array
    {
        $em = $this->doctrine->getManager();
        $subscriptionPlan = $em->getRepository(CompanySubscriptionPlan::class)->findOneBy(['inAppPlan' => $request->request->get('subscriptionPlan')]);
        $amount = ($subscriptionPlan->getInappAmount() == $request->request->get('amount')) ? $subscriptionPlan->getInappAmount() : null;
        if (is_null($amount)) {
            $data['paymentStatus'] = false;
        }
        $transactionDetails = $this->getInappTransactionDetails($receiptResponse, $transactionId);
        $this->companyService->updateCompanyExpiry($subscriptionPlan, $user);
        $returnData['transactionId'] = $transactionId;
        $returnData['amount'] = number_format((float)$amount, 2, '.', '');
        $createdDate = new \DateTime();
        $transactionTime = array_key_exists('original_purchase_date_ms', $transactionDetails) ? $transactionDetails['original_purchase_date_ms'] / 1000 : $createdDate->getTimestamp();

        $returnData['date'] = $createdDate->setTimeStamp($transactionTime);
        $returnData['period'] = $subscriptionPlan->getPeriod();
        $returnData['status'] = $this->translator->trans('paymentSuccessStatus', array(), null, $user->getLanguage());
        $data['paymentReturn'] = $returnData;
        $data['paymentStatus'] = true;

        $this->savePaymentDetails($request->get('transactionId'), $amount, true, 'InApp company payment success', $role, null, true, $user);

        return $data;
    }

    /**
     * @param StripeClient $stripeClient
     * @param UserIdentity $user
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function cancelCompanySubscription(StripeClient $stripeClient, UserIdentity $user)
    {
        $em = $this->doctrine->getManager();
        $subscription = $stripeClient->subscriptions->retrieve($user->getStripeSubscription());
        $subscription->cancel();
        $stripeSubscription = $user->getStripeSubscription();
//        $user->setStripeSubscription(null);
        $user->setStripeSubscription(null);
        $user->setIsRecurring(false);
        $user->setIsExpired(true);
        $user->setSubscriptionCancelledAt(new \DateTime('now'));
        $em->getRepository(UserIdentity::class)->setCompanyUserStatus($user);

        $paymentHistory = $em->getRepository(Payment::class)->findOneBy(['user' => $user, 'transactionId' => $stripeSubscription]);
        if ($paymentHistory instanceof Payment) {
            $paymentHistory->setCancelledDate(new \DateTime('now'));
            $paymentHistory->setEndDate(new \DateTime('now'));
        }
        $em->flush();
    }

    /**
     * Cancel a property subscription if subscription exists
     *
     * @param StripeClient $stripeClient
     * @param Property $property
     * @return void
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function cancelPropertySubscription(StripeClient $stripeClient, Property $property): void
    {
        //if current plan is free plan, no need to cancel
        if (true === $property->getSubscriptionPlan()->getInitialPlan()) {
            return;
        }
        $em = $this->doctrine->getManager();
        $subscription = $stripeClient->subscriptions->retrieve($property->getStripeSubscription());
        $subscription->cancel();
//        $property->setStripeSubscription(null);
        $property->setRecurring(false);
        $property->setIsCancelledSubscription(true);
        $property->setActive(false);
        $currentDate = new \DateTime('now');
        $property->setCancelledDate($currentDate);
//        $property->setSubscriptionPlan(null);
        $em->getRepository(Apartment::class)->setObjectStatus($property, false);
        $paymentHistory = $em->getRepository(Payment::class)->findOneBy(['property' => $property, 'transactionId' => $property->getStripeSubscription()]);
        if ($paymentHistory instanceof Payment) {
            $paymentHistory->setCancelledDate(new \DateTime('now'));
            $paymentHistory->setEndDate(new \DateTime('now'));
        }
        $em->flush();
    }

    /**
     * @param Request $request
     * @param UserIdentity $user
     * @param StripeClient $stripeClient
     * @param string $role
     * @param \Stripe\Customer|null $customer
     * @return array
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function propertyPayment(Request $request, UserIdentity $user, StripeClient $stripeClient, string $role, \Stripe\Customer $customer = null): array
    {
        $result = [];
        $em = $this->doctrine->getManager();
        foreach ($request->get('propertyArray') as $propertyId) {
            $property = $em->getRepository(Property::class)->findOneBy(['publicId' => $propertyId['propertyId']]);
            $period = $propertyId['period'];
            if ($property instanceof Property) {
                $subscriptionPlan = $this->propertyService->getSubscriptionPlan($property, $period);
                if ($propertyId['recurring']) {
                    $result = $this->recurring($subscriptionPlan, $property, $request->get('planChange'), $period, $user, $role, $customer, $stripeClient);
                } else {
                    $result = $this->singlePayment($customer, $property, $request->get('planChange'), $period, $user, $role, $stripeClient, $subscriptionPlan);
                }
            }
        }
        return $result;
    }

    /**
     * companyPayment
     *
     * @param Request $request
     * @param UserIdentity $user
     * @param StripeClient $stripeClient
     * @param \Stripe\Customer|null $customer
     * @return array
     * @throws \Exception
     */
    public function companyPayment(Request $request, UserIdentity $user, StripeClient $stripeClient, string $role, \Stripe\Customer $customer = null): array
    {
        $em = $this->doctrine->getManager();
        $freeSubscriptionPlan = $em->getRepository(CompanySubscriptionPlan::class)->findOneBy(['initialPlan' => 1]);
        $requestedSubscriptionPlan = $em->getRepository(CompanySubscriptionPlan::class)->findOneBy(['stripePlan' => $request->get('subscriptionPlan')]);
        if (!$requestedSubscriptionPlan instanceof CompanySubscriptionPlan) {
            throw new InvalidArgumentException('noSubscription');
        }

        if ($requestedSubscriptionPlan->getId() === $freeSubscriptionPlan->getId() &&
            $user->getIsFreePlanSubscribed() === true) {
            throw new InvalidArgumentException('repeatedFreeSubscription');
        }

        if ($request->request->has('recurring') && $request->request->get('recurring') === true) {
            $payment = $this->companyRecurringPayment($stripeClient, $customer, $user, $role, $requestedSubscriptionPlan);
        } else {
            $payment = $this->companySinglePayment($stripeClient, $customer, $user, $role, $request);
            if (array_key_exists('paymentStatus', $payment) && $payment['paymentStatus'] === false) {
                throw new \Exception('paymentFailed');
            }
        }
        $data = array();
        if (array_key_exists('paymentReturn', $payment)) {
            $data[] = $payment['paymentReturn'];
        }
        return $data;
    }

    /**
     * propertyInAppPayment
     *
     * @param Request $request
     * @param UserIdentity $user
     * @param array $receiptResponse
     * @param StripeClient $stripeClient
     * @return array
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Exception
     */
    public function propertyInAppPayment(Request $request, UserIdentity $user, array $receiptResponse, StripeClient $stripeClient, string $role): array
    {
        $result = [];
        $em = $this->doctrine->getManager();
        foreach ($request->get('propertyArray') as $propertyId) {
            $property = $em->getRepository(Property::class)->findOneBy(['publicId' => $propertyId['propertyId']]);
            if ($property instanceof Property) {
                $subscriptionPlan = $this->propertyService->getSubscriptionPlan($property, $propertyId['period']);
                //Need to validate the plan id with the plan id from database
                $result[] = $this->updatePropertySubscription($subscriptionPlan, $property, $property['amount'], $user, $receiptResponse, $request->get('transactionId'), $role);
                $this->cancelPropertySubscription($stripeClient, $property); // cancelling stripe subscription if exists.
            }
        }
        return $result;
    }

    /**
     * companyInAppPayment
     *
     * @param Request $request
     * @param UserIdentity $user
     * @param array $receiptResponse
     * @param StripeClient $stripeClient
     * @param string $role
     * @return array
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Exception
     */
    public function companyInAppPayment(Request $request, UserIdentity $user, array $receiptResponse, StripeClient $stripeClient, string $role): array
    {
        $data = [];
        $em = $this->doctrine->getManager();
        $freeSubscriptionPlan = $em->getRepository(SubscriptionPlan::class)->findOneBy(['initialPlan' => 1]);
        $requestedSubscriptionPlan = $em->getRepository(SubscriptionPlan::class)->findOneBy(['inAppPlan' => $request->get('subscriptionPlan')]);
        if (!$requestedSubscriptionPlan instanceof SubscriptionPlan) {
            throw new InvalidArgumentException('noSubscription');
        }
        if ($requestedSubscriptionPlan->getId() == $freeSubscriptionPlan->getId() &&
            $user->getIsFreePlanSubscribed() === true) {
            throw new BadMethodCallException('repeatedFreeSubscription');
        }
        $payment = $this->updateCompanySubscription($user, $request, $receiptResponse, $request->get('transactionId'), $role);
        if (!is_null($user->getStripeSubscription())) {
            $this->cancelCompanySubscription($stripeClient, $user);
        }

        if (array_key_exists('paymentStatus', $payment) && $payment['paymentStatus'] === false) {
            throw new \Exception('paymentFailed');
        }

        if (array_key_exists('paymentReturn', $payment)) {
            $data[] = $payment['paymentReturn'];
        }

        return $data;
    }

    /**
     * companyRecurringPayment
     *
     * @param StripeClient $stripeClient
     * @param \Stripe\Customer $customer
     * @param UserIdentity $user
     * @param string $role
     * @param CompanySubscriptionPlan $subscriptionPlan
     *
     * @return array
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function companyRecurringPayment(StripeClient $stripeClient, \Stripe\Customer $customer, UserIdentity $user, string $role, CompanySubscriptionPlan $subscriptionPlan): array
    {
        $data = [];
        $curDate = new \DateTime();
        $isSuccess = false;
        $expiryDate = $user->getExpiryDate();
        $expiringDays = $curDate->diff($expiryDate)->format('%r%a');
        $trialPeriod = ($expiringDays > 0) ? abs($expiringDays) : 0;
        $return = $stripeClient->subscriptions->create(array(
            "customer" => $customer->id,
            "items" => array(
                array(
                    "plan" => $subscriptionPlan->getStripePlan(),
                ),
            ),
            "trial_period_days" => $trialPeriod,
            "metadata" => array(
                "company" => $user->getPublicId(),
                "role" => $role
            )
        ));
        if ($return->status === "active" || $return->status === 'trialing') {
            $isSuccess = true;
            $msg = ($return->status === "trialing") ? 'paymentTrailing' : 'paymentSuccessStatus';
            $this->companyService->updateCompanyExpiry($subscriptionPlan, $user, true, $return->id);
            $returnData['transactionId'] = $return->id;
            $returnData['amount'] = number_format((float)$return->plan->amount / 100, 2, '.', '');
            $createdDate = new \DateTime('now');
            $returnData['date'] = $createdDate->setTimeStamp($return->created);
            $returnData['status'] = $this->translator->trans($msg, array(), null, $user->getLanguage());
            $data['paymentReturn'] = $returnData;
            $data['period'] = $subscriptionPlan->getPeriod();
        }
        $this->savePaymentDetails($return->id, $subscriptionPlan->getAmount(), $isSuccess, $return->status, $role, null, true, $user);
        return $data;
    }

    /**
     * companySinglePayment
     *
     * @param StripeClient $stripeClient
     * @param \Stripe\Customer $customer
     * @param UserIdentity $user
     * @param string $role
     * @param Request $request
     * @return array
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function companySinglePayment(StripeClient $stripeClient, \Stripe\Customer $customer, UserIdentity $user, string $role, Request $request): array
    {
        $data = [];
        $em = $this->doctrine->getManager();
        $subscriptionPlan = $em->getRepository(CompanySubscriptionPlan::class)->findOneBy(['stripePlan' => $request->get('subscriptionPlan')]);
        $amount = ($subscriptionPlan->getAmount() == $request->get('amount')) ? $subscriptionPlan->getAmount() : null;
        if (is_null($amount)) {
            $data['paymentStatus'] = false;
        }

        $isSuccess = false;
        $finalAmount = $amount * 100;
        $return = $stripeClient->charges->create(array(
            "customer" => $customer->id,
            "amount" => $finalAmount,
            "currency" => $this->params->get('default_currency'),
            "metadata" => array(
                "company" => $user->getPublicId(),
                "role" => $role,
                "plan" => $subscriptionPlan->getPublicId()
            )
        ));
        if ($return->status === "succeeded") {
            $this->companyService->updateCompanyExpiry($subscriptionPlan, $user);
            $isSuccess = true;
            $returnData['transactionId'] = $return->id;
            $returnData['amount'] = number_format((float)$return->amount / 100, 2, '.', '');
            $createdDate = new \DateTime('now');
            $returnData['date'] = $createdDate->setTimeStamp($return->created);
            $returnData['period'] = $subscriptionPlan->getPeriod();
            $returnData['status'] = $this->translator->trans('paymentSuccessStatus', array(), null, $user->getLanguage());
            $data['paymentReturn'] = $returnData;
            $data['paymentStatus'] = true;
        }

        $this->savePaymentDetails($return->id, $amount, $isSuccess, $return->status, $role, null, true, $user);
        return $data;
    }

    /**
     *
     * @param Request $request
     * @return array
     */
    public function getSearchCriterion(Request $request): array
    {
        if (empty($request->get('startDate')) || empty($request->get('endDate'))) {
            return false;
        }
        $data['email'] = !empty($request->get('email')) ? $request->get('email') : null;
        $data['transactionId'] = !empty($request->get('transactionId')) ? $request->get('transactionId') : null;
        $data['startDate'] = !empty($request->get('startDate')) ? $request->get('startDate') . ' 00:00:00' : null;
        $data['endDate'] = !empty($request->get('endDate')) ? $request->get('endDate') . ' 23:59:59' : null;

        return $data;
    }

    /**
     * logPayment
     *
     * @param PaymentLink $paymentLink
     * @param object $plan
     * @return void
     */
    public function logPayment(PaymentLink $paymentLink, object $plan)
    {
        $em = $this->doctrine->getManager();
        $paymentLog = new PaymentLog();
        $userId = $paymentLink->metadata['user'] ? $paymentLink->metadata['user'] : $paymentLink->metadata['company'];
        $user = $em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $userId]);
        if ($user instanceof UserIdentity) {
            $paymentLog->setUser($user);
        }
        $paymentLog->setUrl($paymentLink->url);
        $paymentLog->setAmount($plan->getAmount());
        $paymentLog->setPaymentId($paymentLink->id);
        $em->persist($paymentLog);
        $em->flush();
    }

    /**
     * checkoutSessionCompleted
     *
     * @param array $eventData
     * @param UserIdentity $user
     * @param StripeClient $stripeClient
     * @return void
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Exception
     */
    public function checkoutSessionCompleted(array $eventData, UserIdentity $user, StripeClient $stripeClient): void
    {
        $em = $this->doctrine->getManager();
        $stripeEvent = new StripeEvent();
        $stripeEvent->setEventId($eventData['id']);
        $em->persist($stripeEvent);
        $property = $company = null;
        $isCompany = false;
        $metaData = $eventData['data']['object']['metadata'];
        if (empty(array_intersect(array_flip($metaData), ['company', 'property']))) {
            throw new InvalidArgumentException('inValidArgument', 400);
        }
        $role = $metaData['role'];
        $subscription = $stripeClient->subscriptions->retrieve($eventData['data']['object']['subscription']);
        if (array_key_exists('company', $metaData)) {
            $company = $em->getRepository(UserIdentity::class)
                ->findOneBy(['publicId' => $metaData['company']]);
            if ($company instanceof UserIdentity) {
                $this->handleCompanySubscriptionRenewal($company, $subscription, $stripeClient);
            }
            $isCompany = true;
        }
        if (array_key_exists('property', $metaData)) {
            $property = $em->getRepository(Property::class)
                ->findOneBy(['publicId' => $metaData['property']]);
            if ($property instanceof Property && $subscription['items']->data[0]->plan->id != '') {
                $this->handlePropertySubscriptionRenewal($property, $subscription, $stripeClient);
            }
        }
        $amount = $eventData['data']['object']['amount_total'];
        $finalAmount = $amount / 100;
        $em->flush();
        $this->savePaymentDetails($subscription, $finalAmount, true,
            $eventData['data']['object']['id'], $role, $property, $isCompany, $company, $user);
    }

    /**
     * handleCompanySubscription
     *
     * @param UserIdentity $company
     * @param \Stripe\Subscription $subscription
     * @param StripeClient $stripeClient
     * @return void
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function handleCompanySubscriptionRenewal(UserIdentity $company, \Stripe\Subscription $subscription, StripeClient $stripeClient)
    {
        $em = $this->doctrine->getManager();
        if (is_null($company->getSubscriptionCancelledAt()) && !is_null($company->getStripeSubscription())) {
            $this->cancelCompanySubscription($stripeClient, $company);
        }
        //                $date = $company->getExpiryDate() ? $company->getExpiryDate() : new \DateTime();
        $newPlan = $em->getRepository(CompanySubscriptionPlan::class)->findOneBy(['stripePlan' => $subscription['items']->data[0]->plan->id]);
        $date = new \DateTime();
        $planEndDate = $date->setTimestamp($subscription['current_period_end']);
        $company->setPlanEndDate($planEndDate);
        $company->setIsExpired(false);
        $company->setExpiryDate(null);
        $company->setStripeSubscription($subscription['id']);
        $company->setCompanySubscriptionPlan($newPlan);
        $company->setSubscriptionCancelledAt(null);
        $em->getRepository(UserIdentity::class)->setCompanyUserStatus($company);
        $em->flush();
    }

    /**
     * handlePropertySubscriptionRenewal
     *
     * @param Property $property
     * @param \Stripe\Subscription $subscription
     * @param StripeClient $stripeClient
     * @return void
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function handlePropertySubscriptionRenewal(Property $property, \Stripe\Subscription $subscription, StripeClient $stripeClient)
    {
        $em = $this->doctrine->getManager();
        //        $date = $property->getPlanEndDate() ? $property->getPlanEndDate() : new \DateTime();
        if (is_null($property->getCancelledDate())) {
            $this->cancelPropertySubscription($stripeClient, $property);
        }
        $newPlan = $em->getRepository(SubscriptionPlan::class)->findOneBy(['stripePlan' => $subscription['items']->data[0]->plan->id]);
        $date = new \DateTime();
        $period = $date->setTimestamp($subscription['current_period_end']);
        //set objects active
        $property->setActive(true);
        $property->setPlanEndDate($period);
        $property->setPendingPayment(false);
        $property->setStripeSubscription($subscription['id']);
        $property->setSubscriptionPlan($newPlan);
        $property->setCancelledDate(null);
        $property->setIsCancelledSubscription(false);
        $property->setExpiredDate(null);
        $em->getRepository(Apartment::class)->setObjectStatus($property, false);
        $em->flush();
    }

    /**
     * propertyPaymentLink
     *
     * @param Request $request
     * @param Role $role
     * @param string $redirectTo
     * @param UserIdentity $user
     * @param StripeClient $stripeClient
     * @return array
     * @throws InvalidRequestException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function propertyPaymentLink(Request $request, Role $role, string $redirectTo, UserIdentity $user, \Stripe\StripeClient $stripeClient): array
    {
        $em = $this->doctrine->getManager();

        $selectedPlan = $em->getRepository(SubscriptionPlan::class)->findOneBy(['publicId' => $request->request->get('selectedPlan')]);
        $property = $em->getRepository(Property::class)->findOneBy(['publicId' => $request->request->get('property')]);
        list($type, $data) = explode(" ", $request->headers->get('authorization'), 2);
        if (strcasecmp($type, "Bearer") == 0) {
            $redirectTo .= $data . '/';
        }
        $redirectTo .= $property->getPublicId() . '/' . $role->getRoleKey();
        //            $redirectTo = $parameterBag->get('post_payment_url') . '/' . $property->getPublicId();
        $paymentLink = $stripeClient->paymentLinks->create([
            'line_items' => [
                [
                    'price' => $selectedPlan->getStripePlan(),
                    'quantity' => 1,
                ]
            ],
            'metadata' => [
                "property" => $property->getPublicId(),
                "user" => $user->getPublicId(),
                "role" => $role->getPublicId()
            ],
            'after_completion' => [
                'type' => 'redirect',
                'redirect' => [
                    'url' => $redirectTo
                ],
            ],
        ]);
        if (!$paymentLink instanceof PaymentLink) {
            throw new InvalidRequestException('payment_link_error');
        }
        $property->setPaymentLink($paymentLink->id);
        $this->logPayment($paymentLink, $selectedPlan);
        $em->flush();

        return ['paymentLink' => $paymentLink->url];
    }

    /**
     * companyPaymentLink
     *
     * @param Request $request
     * @param UserIdentity $user
     * @param Role $role
     * @param string $redirectTo
     * @param StripeClient $stripeClient
     * @return array
     * @throws InvalidRequestException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function companyPaymentLink(Request $request, UserIdentity $user, Role $role, string $redirectTo, \Stripe\StripeClient $stripeClient): array
    {
        $em = $this->doctrine->getManager();

        $selectedPlan = $em->getRepository(CompanySubscriptionPlan::class)->findOneBy(['publicId' => $request->request->get('selectedPlan')]);
        list($type, $data) = explode(" ", $request->headers->get('authorization'), 2);
        if (strcasecmp($type, "Bearer") == 0) {
            $redirectTo .= $data . '/' . $role->getRoleKey();
        }
        $paymentLink = $stripeClient->paymentLinks->create([
            'line_items' => [
                [
                    'price' => $selectedPlan->getStripePlan(),
                    'quantity' => 1,
                ]
            ],
            'metadata' => [
                "company" => $user->getPublicId(),
                "role" => $role->getPublicId()
            ],
            'after_completion' => [
                'type' => 'redirect',
                'redirect' => [
                    'url' => $redirectTo
                ],
            ],
        ]);
        if (!$paymentLink instanceof PaymentLink) {
            throw new InvalidRequestException('payment_link_error');
        }
        $user->setPaymentLink($paymentLink->id);
        $this->logPayment($paymentLink, $selectedPlan);
        $em->flush();

        return ['paymentLink' => $paymentLink->url];
    }

    /**
     * cancelSubscription
     *
     * @param StripeClient $stripeClient
     * @param string $type
     * @param \Stripe\Subscription $subscriptionPlan
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function cancelSubscription(\Stripe\StripeClient $stripeClient, string $type, \Stripe\Subscription $subscriptionPlan): void
    {
        $em = $this->doctrine->getManager();
        if ($type == 'property') {
            $property = $em->getRepository(Property::class)->findOneBy(['stripeSubscription' => $subscriptionPlan->id]);
            if ($property instanceof Property && !$property->getIsCancelledSubscription()) {
                $this->cancelPropertySubscription($stripeClient, $property);
            }
        } else {
            $user = $em->getRepository(UserIdentity::class)->findOneBy(['stripeSubscription' => $subscriptionPlan->id]);
            if ($user instanceof UserIdentity && is_null($user->getSubscriptionCancelledAt())) {
                $this->cancelCompanySubscription($stripeClient, $user);
            }
        }
    }

    /**
     * formatPropertySubscriptionDetails
     *
     * @param Payment $payment
     * @param string|null $locale
     * @return array
     */
    public function formatPropertySubscriptionDetails(Payment $payment, ?string $locale = 'en'): array
    {
        $data['property'] = $payment->getProperty()->getAddress();
        $data['startDate'] = $payment->getStartDate();
        $data['endDate'] = $payment->getEndDate();
        $data['expiredAt'] = $payment->getProperty()->getExpiredDate();
        $data['cancelledDate'] = $payment->getCancelledDate();
        $data['active'] = $payment->getProperty()->getActive();
        $data['publicId'] = $payment->getPublicId();
        if ($payment->getSubscriptionPlan() instanceof SubscriptionPlan) {
            $data['planDetails'] = ($locale == 'de') ? $payment->getSubscriptionPlan()->getNameDe() : $payment->getSubscriptionPlan()->getName();
        }
        return $data;
    }

    /**
     * formatCompanySubscriptionDetails
     *
     * @param Payment $payment
     * @param string|null $locale
     * @return array
     */
    public function formatCompanySubscriptionDetails(Payment $payment, ?string $locale = 'en'): array
    {
        $data['user'] = $payment->getUser()->getFirstName();
        $data['startDate'] = $payment->getStartDate();
        $data['endDate'] = $payment->getEndDate();
        $data['cancelledDate'] = $payment->getCancelledDate();
        $data['expiredAt'] = $payment->getExpiredAt();
        $data['publicId'] = $payment->getPublicId();
        if ($payment->getCompanyPlan() instanceof CompanySubscriptionPlan) {
            $data['planDetails'] = ($locale == 'de') ? $payment->getCompanyPlan()->getNameDe() : $payment->getCompanyPlan()->getName();
        }
        return $data;
    }
}
