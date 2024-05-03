<?php

/**
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Controller;

use App\Entity\CompanySubscriptionPlan;
use App\Entity\Payment;
use App\Entity\Property;
use App\Entity\Role;
use App\Entity\StripeEvent;
use App\Entity\SubscriptionPlan;
use App\Entity\UserIdentity;
use App\Service\PaymentService;
use App\Service\PropertyService;
use App\Service\SecurityService;
use App\Utils\GeneralUtility;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Exception\InvalidParameterException;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\CardException;
use Stripe\Exception\RateLimitException;
use Stripe\Exception\SignatureVerificationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Nelmio\ApiDocBundle\Annotation\Security;
use FOS\RestBundle\View\View;
use Symfony\Component\Serializer\Exception\BadMethodCallException;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Service\DMSService;
use Symfony\Component\Form\Exception\InvalidArgumentException;

/**
 * PaymentController
 *
 * Controller to manage payment.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 * @Route("/payment")
 */
final class PaymentController extends BaseController
{
    private $stripe;

    public function __construct(RequestStack $request, TranslatorInterface $translator, ManagerRegistry $doctrine, ParameterBagInterface $parameterBag, SecurityService $securityService)
    {
        parent::__construct($request, $translator, $doctrine, $parameterBag, $securityService);
        $this->stripe = new \Stripe\StripeClient($parameterBag->get('stripe_secret'));
    }

    /**
     * API end point to log the payment details
     * @Operation(
     *      tags={"Payment"},
     *      summary="API end point to log the payment details",
     *      @Security(name="Bearer"),
     *   ),
     * @OA\Response(
     *     response=200,
     *     description="Returns success message after creating ticket",
     *  ),
     * @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     *  ),
     * @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     *  ),
     * @OA\Tag(name="Payment")
     * )
     * @param Request $request
     * @param LoggerInterface $logger
     * @param PaymentService $paymentService
     * @param GeneralUtility $generalUtility
     * @return View
     * @throws \Exception
     * @Route("/webhook", name="balu_stripe_webhook", methods={"POST"})
     */
    public function webhook(Request $request, LoggerInterface $logger, PaymentService $paymentService, GeneralUtility $generalUtility): View
    {
        $em = $this->doctrine->getManager();
        $endpointSecret = $this->parameterBag->get('stripe_webhook_secret');
        $payload = $request->getContent();
        $sigHeader = $request->server->get('HTTP_STRIPE_SIGNATURE');
        $event = null;
        $em->beginTransaction();
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sigHeader, $endpointSecret
            );
            $eventData = json_decode($payload, true);
            $metadata = $eventData['data']['object']['metadata'];
            $userUuid = isset($metadata['company']) ? $metadata['company'] : $metadata['user'];
            $user = $em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $userUuid]);
            $stripeEvent = $em->getRepository(StripeEvent::class)->findOneBy(['eventId' => $eventData['id']]);
            switch ($eventData['type']) {
                case ('checkout.session.completed' && $eventData['data']['object']['payment_status'] === 'paid' && !$stripeEvent instanceof StripeEvent):
                    $paymentService->checkoutSessionCompleted($eventData, $user, $this->stripe);
                    break;
                case (('invoice.payment_succeeded' || 'charge.succeeded') && !$stripeEvent instanceof StripeEvent):
                    $paymentService->invoicePaymentSucceed($eventData, $user);
                    break;
                case ('customer.subscription.updated' && !$stripeEvent instanceof StripeEvent) :
                    $paymentService->customerSubscriptionUpdate($eventData, $user);
                    break;
                default:
                    throw new \Exception('invalidEvent');
            }
            $em->commit();
            $data = $generalUtility->handleSuccessResponse('webhookSuccess');
        } catch (\UnexpectedValueException | SignatureVerificationException $e) {
            $em->rollback();
            $curDate = new \DateTime();
            $logger->error($curDate->format("Y-m-d H:i:s") . ' ' . 'webhook_failed' . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse('webhookFailed');
        }
        return $this->response($data);
    }

    /**
     * API end point to do the payment.
     *
     * # Request
     * In request body, system expects property details as application/json.
     * ## Example request to edit new property
     * ### Route /api/2.0/payment/{type}
     * ### type=company for companies and type=property for property payments
     * ### For properties, request goes
     *      {
     *            "paymentToken": "tok_1M2AEEKICx2XsqvxHcirKJh2",
     *            "propertyArray": [
     *                   {
     *                          "propertyId": "1ecb66da-d42e-6784-bad1-0242ac120003",
     *                          "recurring": true,
     *                          "amount": 10,
     *                          "period": 30
     *                    },
     *                   {
     *                           "propertyId": "1ecb66da-d42e-6784-bad1-0242ac120003",
     *                           "recurring": true,
     *                           "amount": 10,
     *                           "period": 30
     *                    }
     *            ],
     *            "planChange": true
     *      }
     * ### For company type, request goes
     *       {
     *            "paymentToken": "tok_1M2AEEKICx2XsqvxHcirKJh2",
     *            "recurring": true,
     *            "amount": 12.0,
     *            "subscriptionPlan": "plan_xxxxxxxx"
     *      }
     * # Response
     * ## Success response ##
     *      {
     *           "data": {
     *                 "amount": "12.01",
     *                 "status": "paymentSuccessStatus",
     *                 "transactionId": "ch_3M537cKICx2Xsqvx0temFeYg",
     *                 "date": "2022-11-17T13:36:56+05:30",
     *                 "address": "erer",
     *                 "period": 30
     *          },
     *          "error": false,
     *          "message": "Payment Completed"
     *      }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "propertyCreateFail"
     *       }
     * @Route("/{type}", name="balu_payment", methods={"POST"})
     * @Operation(
     *      tags={"Payment"},
     *      summary="API end point to do a payemnt.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               @OA\Property(property="paymentToken", type="string", default="", example="tok_1M2AEEKICx2XsqvxHcirKJh2"),
     *               @OA\Property(
     *                      property="propertyArray",
     *                      type="array",
     *                      @OA\Items(
     *                           @OA\Property(property="propertyId", type="string", default="", example="1eccb6f1-68a5-6fa4-b233-0242ac120003"),
     *                           @OA\Property(property="recurring", type="boolean", default="", example=true),
     *                           @OA\Property(property="amount", type="string", default="", example=10),
     *                           @OA\Property(property="period", type="string", default="", example=30),
     *                     ),
     *                     @OA\Items(
     *                           @OA\Property(property="propertyId", type="string", default="", example="1eccb6f1-68a5-6fa4-b233-0242ac120003"),
     *                           @OA\Property(property="recurring", type="boolean", default="", example=true),
     *                           @OA\Property(property="amount", type="string", default="", example=10),
     *                           @OA\Property(property="period", type="string", default="", example=365),
     *                     ),
     *               ),
     *               @OA\Property(property="planChange", type="boolean", default="", example=false),
     *           )
     *       )
     *     ),
     *     @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               @OA\Property(property="paymentToken", type="string", default="", example="tok_1M2AEEKICx2XsqvxHcirKJh2"),
     *               @OA\Property(property="recurring", type="string", default="", example="true"),
     *               @OA\Property(property="amount", type="string", default="", example="212.0"),
     *               @OA\Property(property="subscriptionPlan", type="string", default="", example="plan_xxxxxxxx"),
     *           )
     *       )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returned on successful response"
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Returned when request is not valid"
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="User not authenticated"
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Internal Error"
     *     )
     *)
     * @param string $type
     * @param Request $request
     * @param PaymentService $paymentService
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param SecurityService $securityService
     * @return View
     */
    public function payment(string $type, Request $request, PaymentService $paymentService,
                            GeneralUtility $generalUtility, LoggerInterface $requestLogger, SecurityService $securityService): View
    {
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            if (empty($request->request->get('paymentToken'))) {
                throw new CardException('missingPaymentToken');
            }
            $customer = null;
            $user = $this->getUser();
            $customer = $this->stripe->customers->create(array(
                'email' => $user->getUser()->getProperty(),
                'source' => $request->get('paymentToken')
            ));
            switch ($type) {
                case 'property':
                    $result = $paymentService->propertyPayment($request, $user, $this->stripe, $this->currentRole, $customer);
                    break;
                case 'company':
                    $userRoles = $securityService->fetchUserRole($user);
                    if (!in_array('company', $userRoles['key'])) {
                        throw new InvalidArgumentException('notACompanyUser');
                    }
                    $result = $paymentService->companyPayment($request, $user, $this->stripe, $this->currentRole, $customer);
                    break;
                default:
                    throw new InvalidArgumentException('invalidType');
            }
            $em->flush();
            if (empty($result)) {
                throw new BadMethodCallException('paymentFailed');
            }
            $em->commit();
            $data = $generalUtility->handleSuccessResponse('paymentComplete', $result);
        } catch (BadMethodCallException | RateLimitException | \Exception $e) {
            $em->rollback();
            $curDate = new \DateTime();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * In app Payment action for Property/Company.
     *
     * # Request
     * In request body, system expects property details as application/json.
     * ## Example request to edit new property
     * ### Route /api/2.0/payment/in-app/{type}
     * ### type=company for companies and type=property for property payments
     * ### For properties, request goes
     *      {
     *            "transactionId": "txn_1M2AEEKICx2XsqvxHcirKJh2",
     *            "receipt": "receipt_xxxxxx",
     *            "propertyArray": [
     *                   {
     *                          "propertyId": "1ecb66da-d42e-6784-bad1-0242ac120003",
     *                          "recurring": true,
     *                          "amount": 10,
     *                          "period": 30
     *                    },
     *                   {
     *                           "propertyId": "1ecb66da-d42e-6784-bad1-0242ac120003",
     *                           "recurring": true,
     *                           "amount": 10,
     *                           "period": 30
     *                    }
     *            ]
     *      }
     *  ### For companies, request goes,
     *      {
     *            "transactionId": "txn_1M2AEEKICx2XsqvxHcirKJh2",
     *            "receipt": "receipt_xxxxxx",
     *            "amount": 12
     *            "subscriptionPlan": "plan_xxxxxxxxx"
     *      }
     * # Response
     * ## Success response ##
     *   {
     *       "data": {
     *           "amount": "12.01",
     *           "status": "paymentSuccessStatus",
     *           "transactionId": "ch_3M537cKICx2Xsqvx0temFeYg",
     *           "date": "2022-11-17T13:36:56+05:30",
     *           "address": "erer",
     *           "period": 30
     *       },
     *       "error": false,
     *       "message": "Payment Completed"
     *   }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "paymentFailed"
     *       }
     * @Route("/in-app/{type}", name="balu_inapp_payment", methods={"POST"})
     * @Operation(
     *      tags={"Payment"},
     *      summary="API end point to do in app payemnt.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               @OA\Property(property="transactionId", type="string", default="", example="txn_1M2AEEKICx2XsqvxHcirKJh2"),
     *               @OA\Property(property="receipt", type="string", default="", example="receipt_xxxxxx"),
     *               @OA\Property(property="userPlan", type="string", default="", example="plan_xxxxxxx"),
     *               @OA\Property(
     *                      property="propertyArray",
     *                      type="array",
     *                      @OA\Items(
     *                           @OA\Property(property="propertyId", type="string", default="", example="1eccb6f1-68a5-6fa4-b233-0242ac120003"),
     *                           @OA\Property(property="recurring", type="boolean", default="", example=true),
     *                           @OA\Property(property="amount", type="string", default="", example=10),
     *                           @OA\Property(property="period", type="string", default="", example=30),
     *                     ),
     *                     @OA\Items(
     *                           @OA\Property(property="propertyId", type="string", default="", example="1eccb6f1-68a5-6fa4-b233-0242ac120003"),
     *                           @OA\Property(property="recurring", type="boolean", default="", example=true),
     *                           @OA\Property(property="amount", type="string", default="", example=10),
     *                           @OA\Property(property="period", type="string", default="", example=365),
     *                     ),
     *               ),
     *               @OA\Property(property="planChange", type="boolean", default="", example=false),
     *           )
     *       )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returned on successful response"
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Returned when request is not valid"
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="User not authenticated"
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Internal Error"
     *     )
     *)
     * @param string $type
     * @param Request $request
     * @param PaymentService $paymentService
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param PropertyService $propertyService
     * @param SecurityService $securityService
     * @return View
     */
    public function inAppPayment(string $type, Request $request, PaymentService $paymentService, GeneralUtility $generalUtility,
                                 LoggerInterface $requestLogger, PropertyService $propertyService, SecurityService $securityService): View
    {
        try {
            $receipt = $request->get('receipt');
            if (empty($receipt)) {
                throw new InvalidArgumentException('invalidReceipt');
            }
            $customer = null;
            $receiptResponse = $paymentService->validateReceipt($receipt);
            $em = $this->doctrine->getManager();
            $user = $this->getUser();
            switch ($type) {
                case 'property':
                    $result = $paymentService->propertyInAppPayment($request, $user, $receiptResponse, $this->stripe, $this->currentRole);
                    break;
                case 'company':
                    $userRoles = $securityService->fetchUserRole($user);
                    if (!in_array('company', $userRoles['key'])) {
                        throw new InvalidArgumentException('notACompanyUser');
                    }
                    $result = $paymentService->companyInAppPayment($request, $user, $receiptResponse, $this->stripe, $this->currentRole);
                    break;
                default:
                    throw new InvalidArgumentException('inValidType');
            }
            $em->flush();
            if (empty($result)) {
                throw new BadMethodCallException('paymentFailed');
            }
            $data = $generalUtility->handleSuccessResponse('paymentComplete', $result);
        } catch (InvalidArgumentException | BadMethodCallException | \Exception $e) {
            $curDate = new \DateTime();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }
        return $this->response($data);
    }

    /**
     * API end point to list Payment details of currently logged in user.
     *
     * # Request
     *
     * @Route("/list", name="balu_list_payments", methods={"GET"})
     * @Operation(
     *      tags={"Payment"},
     *      summary="API end point to get detail of an individual",
     *      @Security(name="Bearer"),
     *     @OA\Response(
     *         response="200",
     *         description="Returned on successful response"
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Returned when request is not valid"
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="User not authenticated"
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Internal Error"
     *     )
     *)
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @param ParameterBagInterface $param
     * @param DMSService $dmsService
     * @return View
     */
    public function list(GeneralUtility $generalUtility, LoggerInterface $requestLogger,
                         Request $request, ParameterBagInterface $param, DMSService $dmsService): View
    {
        $curDate = new \DateTime('now');
        try {
            $user = $this->getUser();
            $em = $this->doctrine->getManager();
            $currentUserRole = $this->currentRole;
            $role = $dmsService->convertSnakeCaseString($currentUserRole);
            $params['offset'] = $request->get('offset');
            $params['limit'] = $request->get('limit');
            $params['searchKey'] = $request->get('searchKey');
            if ($role === $param->get('user_roles')['admin']) {
                $count = $em->getRepository(Payment::class)->getAllPayments($user, $params, $role, true, true);
                $payments = [
                    'count' => $count,
                    'data' => $em->getRepository(Payment::class)->getAllPayments($user, $params, $role, true, false, $this->locale)
                ];
                if (isset($params['limit']) && $params['limit'] && $count > 0) {
                    $payments['maxPage'] = (int)ceil($count / $params['limit']);
                }
            } else {
                $payments = $em->getRepository(Payment::class)->getListOfPaymentsByLoggedInUser($user, $params, $role);
            }
            $data = $generalUtility->handleSuccessResponse('listFetch', $payments);
        } catch (InvalidPasswordException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }
        return $this->response($data);
    }


    /**
     * API end point to get the amount to be payed
     *
     * # Request
     * ##Example Request
     * ### Route /api/2.0/payment/amount/{type}
     * ### type=company for companies and type=property for property payments
     * ### for properties, request goes,
     *         {
     *              "propertyArray": [
     *                {
     *                        "propertyId": "1ecb66da-d42e-6784-bad1-0242ac120003",
     *                        "recurring": true,
     *                        "amount": 10,
     *                        "period": 30
     *                 },
     *                 {
     *                        "propertyId": "1ecb66da-d42e-6784-bad1-0242ac120003",
     *                        "recurring": true,
     *                        "amount": 10,
     *                        "period": 30
     *                  }
     *            ]
     *          },
     * ### for companies, request goes,
     *          {
     *               "subscriptionPlan": "plan_xxxxxxxx"
     *          },
     * # Response
     * ## Success response ##
     *       {
     *           "error": false,
     *           "message": "Amount listed successfully"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Amount fetching failed"
     *       }
     * @Route("/amount/{type}", name="balu_total_amount", methods={"POST"})
     * @Operation(
     *      tags={"Payment"},
     *      summary="API end point to get the amount to be payed",
     *      @Security(name="Bearer"),
     *     ),
     * @OA\Response(
     *         response="200",
     *         description="Returned on successful response"
     *      ),
     * @OA\Response(
     *         response="400",
     *         description="Returned when request is not valid"
     *      ),
     * @OA\Response(
     *          response="401",
     *          description="User not authenticated"
     *      ),
     * @OA\Response(
     *         response="500",
     *         description="Internal Error"
     *      )
     *)
     *
     * @param string $type
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param PropertyService $propertyService
     * @return View
     */
    public function amount(string $type, Request $request, GeneralUtility $generalUtility,
                           LoggerInterface $requestLogger, PropertyService $propertyService): View
    {
        try {
            $em = $this->doctrine->getManager();
            $amount = array();
            switch ($type) {
                case 'property':
                    foreach ($request->request->get('propertyArray') as $propertyId) {
                        $property = $em->getRepository(Property::class)->findOneBy(['publicId' => $propertyId['propertyId']]);
                        // To remove
                        if ($propertyId['period'] === 30) {
                            $period = 1;
                        } elseif ($propertyId['period'] === 365) {
                            $period = 2;
                        } else {
                            $period = $propertyId['period'];
                        }
                        if ($property instanceof Property) {
                            $subscriptionPlan = $propertyService->getSubscriptionPlan($property, $period);
                            if ($subscriptionPlan instanceof SubscriptionPlan)
                                $amount[] = $subscriptionPlan->getAmount();
                        }
                    }
                    break;
                case 'company':
                    $subscriptionPlan = $em->getRepository(CompanySubscriptionPlan::class)
                        ->findOneBy(['stripePlan' => $request->request->get('subscriptionPlan')]);
                    if ($subscriptionPlan instanceof CompanySubscriptionPlan) {
                        $amount[] = $subscriptionPlan->getAmount();
                    }
                    break;
                default:
                    throw new InvalidArgumentException('inValidType');
            }
            if (empty($amount)) {
                throw new InvalidParameterException('amountFetchFailed');
            }
            $data = $generalUtility->handleSuccessResponse('amountFetchSuccess', ['amount' => array_sum($amount)]);
        } catch (InvalidArgumentException | \Exception $e) {
            $curDate = new \DateTime();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }
        return $this->response($data);
    }

    /**
     * API end point to search Payment details of currently logged in user.
     *
     * # Request
     *
     * @Route("/search/pay", name="balu_search_payments", methods={"POST"})
     * @Operation(
     *      tags={"Payment"},
     *      summary="API end point to search Payment details of currently logged in user",
     *      @Security(name="Bearer"),
     *     @OA\Response(
     *         response="200",
     *         description="Returned on successful response"
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Returned when request is not valid"
     *     ),
     *     @OA\Response(
     *          response="401",
     *          description="User not authenticated"
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Internal Error"
     *     )
     *)
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @param ParameterBagInterface $param
     * @param DMSService $dmsService
     * @param PaymentService $paymentService
     * @return View
     */
    public function search(GeneralUtility $generalUtility, LoggerInterface $requestLogger,
                           Request $request, ParameterBagInterface $param, DMSService $dmsService, PaymentService $paymentService): View
    {
        $curDate = new \DateTime('now');
        $payments = [];
        try {
            $em = $this->doctrine->getManager();
            $currentUserRole = $this->currentRole;
            $role = $dmsService->convertSnakeCaseString($currentUserRole);
            $params['offset'] = $request->get('offset');
            $params['limit'] = $request->get('limit');
            $criterion = $paymentService->getSearchCriterion($request);
            if ($criterion !== false) {
                if ($role === $param->get('user_roles')['admin']) {
                    $payments = $em->getRepository(Payment::class)->searchPayment($criterion, $params);
                } else {
                    $payments = $em->getRepository(Payment::class)->searchPayment($criterion, $params);
                }
            }
            $data = $generalUtility->handleSuccessResponse('listFetch', $payments);
        } catch (InvalidPasswordException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }
        return $this->response($data);
    }

    /**
     * API end point to create the payment of stripe
     *
     * # Request
     * ##Example Request
     * ### Route /api/2.0/payment/link/{type}
     * ### type=company for companies and type=property for property payments
     * ### for properties, request goes,
     *         {
     *             "selectedPlan": "1edc3de7-bc7e-6d1a-9e36-00155d01d845",
     *             "property": "1edc7e2f-08a0-68f6-b5a7-00155d01d845"
     *         }
     * ### for companies, request goes,
     *          {
     *               "selectedPlan": "1edc3de7-bc7e-6d1a-9e36-00155d01d845"
     *          },
     * # Response
     * ## Success response ##
     *       {
     *           "error": false,
     *           "currentRole": "owner",
     *            "data": {
     *                   "paymentLink": "https://buy.stripe.com/test_7sI01hg7w7dd3Qs3cs"
     *           },
     *           "message": "Payment link created successfully"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Payment link creation failed"
     *       }
     * @Route("/link/{type}", name="balu_payment_link", methods={"POST"})
     * @Operation(
     *      tags={"Payment"},
     *      summary="API end point to create the payment link of stripe",
     *      @Security(name="Bearer"),
     *     ),
     * @OA\Response(
     *         response="200",
     *         description="Returned on successful response"
     *      ),
     * @OA\Response(
     *         response="400",
     *         description="Returned when request is not valid"
     *      ),
     * @OA\Response(
     *          response="401",
     *          description="User not authenticated"
     *      ),
     * @OA\Response(
     *         response="500",
     *         description="Internal Error"
     *      )
     *)
     *
     * @param string $type
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param PaymentService $paymentService
     * @param ParameterBagInterface $parameterBag
     * @return View
     */
    public function getPaymentLink(string $type, Request $request, GeneralUtility $generalUtility, LoggerInterface $requestLogger,
                                   PaymentService $paymentService, ParameterBagInterface $parameterBag): View
    {
        try {
            $em = $this->doctrine->getManager();
            $currentRole = $em->getRepository(Role::class)->findOneBy(['roleKey' => $this->currentRole]);
            $redirectTo = $parameterBag->get('post_payment_url') . '/';
            switch ($type) {
                case 'property':
                    $data = $paymentService->propertyPaymentLink($request, $currentRole, $redirectTo, $this->getUser(), $this->stripe);
                    break;
                case 'company':
                    $data = $paymentService->companyPaymentLink($request, $this->getUser(), $currentRole, $redirectTo, $this->stripe);
                    break;
                default:
                    throw new InvalidArgumentException('typeError');
            }
            $data = $generalUtility->handleSuccessResponse('paymentLinkCreated', $data);
        } catch (InvalidArgumentException | \Exception $e) {
            $curDate = new \DateTime();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }
        return $this->response($data);
    }

    /**
     * API end point cancel the subscription
     *
     * # Request
     * ##Example Request
     * ### Route /api/2.0/payment/cancel-subscription/{type}
     * ### type=company for companies and type=property for property payments
     * ### for properties, request goes,
     *         {
     *             "property": "1edc3de7-bc7e-6d1a-9e36-00155d01d845"
     *         }
     * ### for companies, request goes,
     *          {
     *               "company": "1edc3de7-bc7e-6d1a-9e36-00155d01d845"
     *          },
     * # Response
     * ## Success response ##
     *       {
     *           "error": false,
     *           "currentRole": "owner",
     *           "data": {
     *           },
     *           "message": "Subscription cancelled successfully"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Subscription cancellation failed"
     *       }
     * @Route("/cancel-subscription/{type}", name="balu_cancel_subscription", methods={"POST"})
     * @Operation(
     *      tags={"Payment"},
     *      summary="API end point cancel the subscription",
     *      @Security(name="Bearer"),
     *     ),
     * @OA\Response(
     *         response="200",
     *         description="Returned on successful response"
     *      ),
     * @OA\Response(
     *         response="400",
     *         description="Returned when request is not valid"
     *      ),
     * @OA\Response(
     *          response="401",
     *          description="User not authenticated"
     *      ),
     * @OA\Response(
     *         response="500",
     *         description="Internal Error"
     *      )
     *)
     *
     * @param string $type
     * @param Request $request
     * @param LoggerInterface $requestLogger
     * @param GeneralUtility $generalUtility
     * @param PaymentService $paymentService
     * @return View
     */
    public function cancelSubscription(string $type, Request $request, LoggerInterface $requestLogger, GeneralUtility $generalUtility,
                                       PaymentService $paymentService): View
    {
        $em = $this->doctrine->getManager();
        $curDate = new \DateTime('now');
        $em->beginTransaction();
        try {
            switch ($type) {
                case 'property':
                    $property = $em->getRepository(Property::class)->findOneBy(['publicId' => $request->get('property')]);
                    if (!$property instanceof Property) {
                        throw new InvalidArgumentException('invalidProperty');
                    }
                    $subscription = $this->stripe->subscriptions->retrieve($property->getStripeSubscription());
                    if (!$subscription instanceof \Stripe\Subscription) {
                        throw new ApiConnectionException('invalidSubscription');
                    }
                    break;
                case 'company':
                    $user = $this->getUser();
                    if (is_null($user->getStripeSubscription())) {
                        throw new InvalidArgumentException('invalidCompanySubscription');
                    }
                    $subscription = $this->stripe->subscriptions->retrieve($user->getStripeSubscription());
                    if (!$subscription instanceof \Stripe\Subscription) {
                        throw new ApiConnectionException('invalidSubscription');
                    }
                    break;
                default:
                    throw new InvalidArgumentException('typeError');
            }
            $paymentService->cancelSubscription($this->stripe, $type, $subscription);
            $em->commit();
            $data = $generalUtility->handleSuccessResponse('unSubscribed');
        } catch (InvalidArgumentException | ApiConnectionException | \Exception $e) {
            $em->rollback();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

//
//    /**
//    * @ApiDoc(
//    *   description="Search payment (pass limit and page along with url) ",
//    *   section="Payment",
//    *   resource=true,
//    *   headers={
//    *   { "name"="Authorization", "description"="Bearer JWT token", "required"=true }
//    *   },
//    *   parameters={
//    *      {"name"="email", "dataType"="string", "required"=false, "description"="Email of user"},
//    *      {"name"="transactionId", "dataType"="string", "required"=false, "description"="Transaction id"},
//    *      {"name"="startDate", "dataType"="date", "required"=false, "description"="Start Date"},
//    *      {"name"="endDate", "dataType"="date", "required"=false, "description"="End Date"},
//    *      {"name"="limit", "dataType"="integer", "required"=false, "description"="Limit to display"},
//    *      {"name"="page", "dataType"="integer", "required"=false, "description"="Page number"},
//    *   },
//    *   output = "\Symfony\Component\HttpFoundation\JsonResponse",
//    *   statusCodes={
//    *     200="Ok",
//    *     401="Invalid JWT authentication",
//     *    400="Bad Request"
//    *   }
//    * )
//    *
//    * Search payment
//    * @param Request $request
//    * @throws
//    * @return view
//    */
//    public function searchAction(Request $request)
//    {
//        $em = $this->getDoctrine()->getManager();
//        $locale = $request->headers->get('locale');
//
//        $user = $this->container->get('security.token_storage')->getToken()->getUser();
//        $userRole = $this->container->get('baluproperty.user.services')->fetchUserRole($user);
//        $userRoleArray = $this->container->getParameter('user_roles');
//
//        if ($user instanceof BpUser && ($userRole === $userRoleArray['admin'])) {
//            $criterion = $this->getSearchCriterion($request);
//            if ($criterion !== false) {
//                $params['limit'] = $request->get('limit') ? (int)$request->get('limit') : $this->container->getParameter('payment_details_list_limit');
//                $params['page'] = $request->get('page') ? (int)$request->get('page') : 1;
//                $params['offset'] = ($params['page'] != 0) ? ($params['page'] - 1) * $params['limit'] : $params['page'];
//
//                $payments = $em->getRepository("AppBundle:BpPayment")->searchPayment($criterion, $params);
//                $result['payments'] = $this->getTranslatedResponse($payments, $locale);
//                $result['count']= $em->getRepository("AppBundle:BpPayment")->searchPayment($criterion, $params, true);
//
//                if ($params['limit'] && $result['count']) {
//                    $result['maxPage'] = (int)ceil($result['count'] / $params['limit']);
//                }
//
//                return $this->buildResponse($result, false, 'fetchSuccessfully', $this->container->getParameter('statuscode_success'));
//            }
//        }
//
//        return $this->buildResponse(null, true, 'badRequest', $this->container->getParameter('statuscode_failed'));
//    }

//    /**
//     * getSearchCriterion
//     *
//     * @param Request $request
//     *
//     * @return boolean/array
//     */
//    private function getSearchCriterion(Request $request)
//    {
//        $data = [];
//
//        if (empty($request->get('startDate')) || empty($request->get('endDate'))) {
//            return false;
//        }
//
//        $data['email'] = !empty($request->get('email')) ? $request->get('email') : null;
//        $data['transactionId'] = !empty($request->get('transactionId')) ? $request->get('transactionId') : null;
//        $data['startDate'] = !empty($request->get('startDate')) ? $request->get('startDate') . ' 00:00:00' : null;
//        $data['endDate'] = !empty($request->get('endDate')) ? $request->get('endDate') . ' 23:59:59' : null;
//
//        return $data;
//    }

//    /**
//     * getTranslatedResponse
//     *
//     * @param array $payments
//     * @param string $locale
//     *
//     * @return array
//     */
//    private function getTranslatedResponse(array $payments, string $locale)
//    {
//        $data = [];
//        $translator = $this->get('translator');
//
//        if (!empty($payments)) {
//            foreach ($payments as $payment) {
//                $payment['userRole'] = $translator->trans($payment['userRole'], array(), null, $locale);
//                $payment['response'] = $translator->trans($payment['response'], array(), null, $locale);
//                $data[] = $payment;
//            }
//        }
//
//        return $data;
//    }
}
