<?php

/**
 * This file is part of the Balu Property package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\CompanySubscriptionPlan;
use App\Entity\Payment;
use App\Entity\Property;
use App\Entity\SubscriptionPlan;
use App\Entity\User;
use App\Form\CompanyRegistrationType;
use App\Service\CompanyService;
use App\Service\DamageService;
use App\Service\PaymentService;
use App\Service\RegistrationService;
use App\Service\UserService;
use App\Utils\Constants;
use App\Utils\GeneralUtility;
use App\Utils\ValidationUtility;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\View\View;
use OpenApi\Annotations as OA;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Psr\Log\LoggerInterface;
use App\Utils\ContainerUtility;
use App\Service\DashboardService;
use App\Entity\UserIdentity;
use App\Entity\UserDevice;

/**
 * UserController
 *
 * Controller to manage User related actions.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 * @Route("/user")
 */
final class UserController extends BaseController
{
    /**
     * API end point to get profile overview of a user
     *
     *
     * # Request
     * If ID is not passed, the details of logged in user will be returned.
     *
     * ## Success response ##
     *
     *      {
     *          "error": false,
     *          "data": {
     *            "uuid": "1ec34f72-e1af-6fde-b32b-df4933035eec",
     *            "email": "test@yopmail.com",
     *            "firstName": "Test",
     *            "lastName": "User",
     *            "isBlocked": false,
     *            "companyName": "company name",
     *            "roles": [
     *                   {
     *                       "roleKey": "company",
     *                       "name": "Company"
     *                   }
     *              ]
     *            "deviceIds": [
     *                "45d0012c-3336-4d7a-b042-d46293b6b823"
     *             ],
     *            "latitude": "10.8012",
     *            "longitude": "10.2",
     *            "street": "street name",
     *            "zipCode": 65499,
     *            "houseNumber": "2344",
     *            "city": "Los Vegas",
     *          }
     *      }
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns profile overview of a user",
     * ),
     * @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     * ),
     * @OA\Response(
     *     response="401",
     *     description="User not authenticated"
     * ),
     * @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     * ),
     * @OA\Response(
     *     response="422",
     *     description="User not found"
     * )
     * @Operation(
     *     summary="API end point to get profile overview.",
     *     @Security(name="Bearer")
     * )
     * @OA\Tag(name="User")
     * @param string|null $user
     * @param UserService $userService
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @return View
     * @Route("/get-profile/{user}", defaults={"user" = null}, name="balu_get_user_profile", methods={"GET"})
     */
    public function getProfile(?string $user, UserService $userService, GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request): View
    {
        $curDate = new \DateTime('now');
        try {
            if (null === $user) {
                $user = $this->getUser()->getPublicId();
            }
            $data = $generalUtility->handleSuccessResponse(
                'profileDetailsFetched',
                $userService->getProfile($user, $this->locale)
            );
        } catch (UserNotFoundException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse('profileDetailsFetchFailed');
        }
        return $this->response($data);
    }

    /**
     * API end point to update user profile.
     *
     * # Request
     * In request body, system expects user details as JSON.
     * ## gender codes are currently male, female, and trans
     * ## Example request to update user settings
     *      {
     *          "companyName" : "company",
     *          "gender": "Male",
     *          "latitude": "",
     *          "longitude" : "",
     *          "street": "street",
     *          "zipCode": "zip Code",
     *          "houseNumber": "house Number",
     *          "city": "city",
     *      }
     * # Response
     * ## Success response ##
     *       {
     *           "data": {
     *              "email": "test@yopmail.com",
     *              "firstName": "Hellen",
     *              "lastName": "keller",
     *              "companyName": "company",
     *              "street": "street",
     *              "streetNumber": "street",
     *              "zipCode": "12345",
     *           },
     *           "error": false
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "User detail update failed"
     *       }
     * @Route("/update-profile", name="balu_update_profile", methods={"POST"})
     * @Operation(
     *      tags={"User"},
     *      summary="API end point to update user details.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               @OA\Property(property="email", type="string", default="", example="hellenkeller@example.com"),
     *               @OA\Property(property="firstName", type="string", default="", example="Hellen"),
     *               @OA\Property(property="lastName", type="string", default="", example="Keller"),
     *               @OA\Property(property="dob", type="string", default="", example="1995-12-22"),
     *               @OA\Property(property="companyName", type="string", default="", example="Hykon"),
     *               @OA\Property(property="street", type="string", default="", example="Las vegas"),
     *               @OA\Property(property="streetNumber", type="string", default="", example="No. 18, LV"),
     *               @OA\Property(property="city", type="string", default="", example="New York city"),
     *               @OA\Property(property="zipCode", type="string", default="", example="20325"),
     *               @OA\Property(property="mobile", type="string", default="", example="0123456789"),
     *               @OA\Property(property="country", type="string", default="", example="India"),
     *               @OA\Property(property="countryCode", type="string", default="", example="IN"),
     *               @OA\Property(property="website", type="string", default="", example="20325"),
     *               @OA\Property(property="isPolicyAccepted", type="boolean", default="", example="true"),
     *               @OA\Property(property="role", type="string", default="", example="owner/admin/tenant"),
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
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param UserService $userService
     * @param LoggerInterface $requestLogger
     * @param DamageService $damageService
     * @return View
     */
    public function updateProfile(Request $request, GeneralUtility $generalUtility, UserService $userService,
                                  LoggerInterface $requestLogger, DamageService $damageService): View
    {
        $curDate = new \DateTime('now');
        $user = $this->getUser();
        $role = $request->request->get('role');
        $formType = "App\\Form\\" . ucfirst($generalUtility->snakeToCamelCaseConverter($role)) . 'ProfileType';
        $form = $this->createNamedForm($formType, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->beginTransaction();
            try {
                $userService->updateUser($form, $user);
                if ($form->has('damage') && !is_null($form->get('damage')->getData())) {
                    $damageService->registerDamageRequestIfNotExists($form->get('damage')->getData(), $user, $user->getUser()->getProperty());
                }
                $em->flush();
                $em->commit();
                $data = $generalUtility->handleSuccessResponse('userUpdatedSuccessful');
            } catch (CustomUserMessageAccountStatusException | \Exception $e) {
                $em->rollback();
                $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
                $data = $generalUtility->handleFailedResponse($e->getMessage());
            }
        } else {
            $data = $generalUtility->handleFailedResponse('formError', 400, $this->getErrorsFromForm($form));
        }
        return $this->response($data);
    }

    /**
     * API end point to get dashboard
     *
     * ## Success response ##
     *
     *       {
     *           "currentRole": "owner",
     *           "data": {
     *               "tickets": {
     *                   "tenant": {
     *                       "name": "Tenant",
     *                       "count": 4,
     *                       "colour": "rgb(184 74 22)"
     *                   },
     *                   "owner": {
     *                       "name": "Owner",
     *                       "count": 4,
     *                       "colour": "rgb(200 220 13)"
     *                   },
     *                   "total": 4
     *               },
     *               "property": {
     *                   "name": "Properties",
     *                   "count": 2
     *               },
     *               "object": {
     *                   "name": "Objects",
     *                   "count":6
     *               },
     *               "tenantCount": {
     *                   "name": "Active Habitants",
     *                   "count": 5
     *               }
     *           },
     *           "error": false,
     *           "message": "dashboardDetailsFetched"
     *       }
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns dashboard overview of a user",
     * ),
     * @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     * ),
     * @OA\Response(
     *     response="401",
     *     description="User not authenticated"
     * ),
     * @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     * ),
     * @OA\Response(
     *     response="422",
     *     description="User not found"
     * )
     * @Operation(
     *     summary="API end point to get dashboard  overview.",
     *     @Security(name="Bearer")
     * )
     * @OA\Tag(name="User")
     * @param Request $request
     * @param DashboardService $dashboardService
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @return View
     * @Route("/dashboard", name="balu_get_dashboard", methods={"GET"})
     */
    public function dashboard(Request $request, DashboardService $dashboardService, GeneralUtility $generalUtility, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        try {
            $data = $generalUtility->handleSuccessResponse(
                'dashboardDetailsFetched',
                $dashboardService->getDashboard($this->getUser(), $this->currentRole, $request)
            );
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }
        return $this->response($data);
    }

    /**
     * API end point to change language
     *
     * # Request
     * In request body, system expects object details as JSON.
     * ## Example request to create new object
     *       {
     *           "language": "en"
     *       }
     *
     * ## Success response ##
     *
     *      {
     *           "data": [],
     *           "error": false,
     *           "message": "Language Changed Successfully."
     *       }
     * @Operation(
     *      tags={"User"},
     *      summary="API end point to change language.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=true,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="language", type="string", default="en", example="en"),
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
     * @Route("/update-language", name="balu_user_language", methods={"POST"})
     *
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param UserService $userService
     * @return View
     */
    public function updateUserLanguage(Request $request, GeneralUtility $generalUtility, LoggerInterface $requestLogger,
                                       UserService $userService): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        $data = $generalUtility->handleSuccessResponse('noDatatoProcess');
        try {
            if (in_array($request->get('language'), Constants::LANGUAGE_CODES)) {
                $data = $generalUtility->handleSuccessResponse('languageChanged',
                    $userService->updateUserLanguage($this->getUser(), $request->get('language')));
                $em->flush();
                $em->commit();
            }
        } catch (\Exception $e) {
            $em->rollBack();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }
        return $this->response($data);
    }


    /**
     * API end point to update company expiry date
     *
     * # Request
     * # Response
     * ## Success response ##
     *       {
     *           "error": false,
     *           "message": "Company exipiry updated successfully"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Property not found"
     *       }
     * @Route("/change-expiry-date", name="balu_user_expiring_change", methods={"POST"})
     * @Operation(
     *      tags={"Property"},
     *      summary="API end point to change expiry date.",
     *      @Security(name="Bearer"),
     *      @OA\Parameter(
     *      name="limit",
     *      in="query",
     *      description="Total number of results to be fetched",
     *      @OA\Schema(type="integer")
     *     ),
     *      @OA\Response(
     *         response="200",
     *         description="Returned on successful response"
     *      ),
     *      @OA\Response(
     *         response="400",
     *         description="Returned when request is not valid"
     *      ),
     *      @OA\Response(
     *          response="401",
     *          description="User not authenticated"
     *      ),
     *      @OA\Response(
     *         response="500",
     *         description="Internal Error"
     *      )
     *)
     *
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @param UserService $userService
     * @return View
     */
    public function changeExpiryDate(GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request, UserService $userService): View
    {
        $em = $this->doctrine->getManager();
        $curTime = new \DateTime('now');
        $data = $generalUtility->handleFailedResponse('fail');
        $user = $em->getRepository(UserIdentity::class)->findOneBy(['deleted' => false, 'identifier' => $request->get('userId')]);
        if (!$user instanceof UserIdentity) {
            return $this->response($generalUtility->handleFailedResponse('invalidUser'));
        }
        $em->beginTransaction();
        try {
            $expiryDateWithTime = 'now';
            if (!empty($request->get('expiryDate'))) {
                $expiryDateWithTime = $request->get('expiryDate') . ' ' . $curTime->format('H:i:s');
            }
            $newExpiryDate = new \DateTime($expiryDateWithTime);
            $currentUserRole = $userService->getCurrentUserRole($this->getUser(), $this->currentRole);
            if ($currentUserRole !== $this->parameterBag->get('user_roles')['admin']) {
                return $this->response($generalUtility->handleFailedResponse('unauthorizedAccess'));
            }
            $user->setExpiryDate($newExpiryDate);
            $expiryDateDiff = $curTime->diff($newExpiryDate)->format('%r%a');
            if ($expiryDateDiff >= 0 && $user->getIsExpired() === true) {
                $user->setIsExpired(false);
            } elseif ($expiryDateDiff < 0 && $user->getIsExpired() === false) {
                $user->setIsExpired(true);
            }
            $user->setUpdatedAt($curTime);
            $em->flush();
            $em->commit();
            $data = $generalUtility->handleSuccessResponse('expiryDateUpdatedSuccessfully', $userService->getProfile($user->getPublicId(), $this->locale));
        } catch (ResourceNotFoundException | \Exception $e) {
            $em->rollback();
            $requestLogger->error($curTime->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to Login as another user
     *
     * # Request
     * # Response
     * ## Success response ##
     *       {
     *           "error": false,
     *           "message": "Company exipiry updated successfully"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Property not found"
     *       }
     * @Route("/_switch_user", name="balu_switch_user", methods={"POST"})
     * @Operation(
     *      tags={"Property"},
     *      summary="API end point to Login as another user.",
     *      @Security(name="Bearer"),
     *      @OA\Parameter(
     *      name="limit",
     *      in="query",
     *      description="Total number of results to be fetched",
     *      @OA\Schema(type="integer")
     *     ),
     *      @OA\Response(
     *         response="200",
     *         description="Returned on successful response"
     *      ),
     *      @OA\Response(
     *         response="400",
     *         description="Returned when request is not valid"
     *      ),
     *      @OA\Response(
     *          response="401",
     *          description="User not authenticated"
     *      ),
     *      @OA\Response(
     *         response="500",
     *         description="Internal Error"
     *      )
     *)
     *
     * @param GeneralUtility $generalUtility
     * @param ContainerUtility $containerUtility
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @param UserService $userService
     * @return View
     */
    public function loginAsAnotherUser(GeneralUtility $generalUtility, ContainerUtility $containerUtility, LoggerInterface $requestLogger, Request $request, UserService $userService): View
    {
        $em = $this->doctrine->getManager();
        $curTime = new \DateTime('now');
        $user = $this->getUser()->getUser();
        $em->beginTransaction();
        try {
            $token = $containerUtility->encryptData($user->getProperty(), true, $this->parameterBag->get('token_expiry_hours'));
            $data = $generalUtility->handleSuccessResponse('validToken', $token);
        } catch (ResourceNotFoundException | \Exception $e) {
            $em->rollback();
            $requestLogger->error($curTime->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to delete a user
     *
     *
     * # Request
     * If ID is not passed, current logged in user will be deleted.
     *
     * ## Success response ##
     *
     *      {
     *          "error": false,
     *          "data": {
     *           }
     *      }
     *
     * @OA\Response(
     *     response=200,
     *     description="Deletes profile of a user",
     * ),
     * @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     * ),
     * @OA\Response(
     *     response="401",
     *     description="User not authenticated"
     * ),
     * @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     * ),
     * @OA\Response(
     *     response="422",
     *     description="User not found"
     * )
     * @Operation(
     *     summary="API end point to delete a user.",
     *     @Security(name="Bearer")
     * )
     * @OA\Tag(name="User")
     * @param string|null $userId
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @return View
     * @Route("/delete/{userId}", defaults={"userId" = null}, name="balu_delete_user_profile", methods={"DELETE"})
     */
    public function deleteProfile(?string $userId, GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request): View
    {
        $curDate = new \DateTime('now');
        $em = $this->getDoctrine()->getManager();
        $data = $generalUtility->handleSuccessResponse('noPermissionToDelete');
        $em->beginTransaction();
        try {
            if (null === $userId) {
                $user = $this->getUser();
            } else {
                $user = $em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $userId, 'deleted' => false]);
            }

            if ($this->currentRole === $this->parameterBag->get('user_roles')['admin'] || $user === $this->getUser()) {
                if ($user instanceof UserIdentity) {
                    $userMail = $user->getUser()->getProperty() . '_deleted_' . date('d-m-Y h:i:s');
                    $user->setProperty($userMail);
                    if ($userDeviceObj = $this->doctrine->getRepository(UserDevice::class)->findBy(['user' => $user, 'deleted' => 0])) {
                        foreach ($userDeviceObj as $userDevice) {
                            $userDevice->setDeleted(true);
                        }
                    }
                    $user->getUser()->setDeleted(true);
                    $user->setEnabled(false)
                        ->setDeleted(true);
                    $data = $generalUtility->handleSuccessResponse('deletedSuccessfully');
                }
            }
            $em->flush();
            $em->commit();
        } catch (UserNotFoundException | \Exception $e) {
            $em->rollback();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse('deleteFailed');
        }
        return $this->response($data);
    }

    /**
     * API end point to get the subscription histories of a user
     *
     *
     * # Request
     * If ID is not passed, current logged in user will be deleted.
     *
     * ## Success response ##
     *
     *      {
     *          "error": false,
     *          "data": {
     *           }
     *      }
     *
     * @OA\Response(
     *     response=200,
     *     description="Deletes profile of a user",
     * ),
     * @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     * ),
     * @OA\Response(
     *     response="401",
     *     description="User not authenticated"
     * ),
     * @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     * ),
     * @OA\Response(
     *     response="422",
     *     description="User not found"
     * )
     * @Operation(
     *     summary="API end point to get the subscription histories of a user",
     *     @Security(name="Bearer")
     * )
     * @OA\Tag(name="User")
     * @param PaymentService $paymentService
     * @param GeneralUtility $generalUtility
     * @return View
     * @Route("/subscription/history", name="balu_subscription_history", methods={"GET"})
     */
    public function subscriptionHistories(PaymentService $paymentService, GeneralUtility $generalUtility): View
    {
        $em = $this->doctrine->getManager();
        $histories = [];
        $subscriptionHistories = $em->getRepository(Payment::class)->findBy(['user' => $this->getUser(), 'deleted' => false], ['identifier' => 'DESC']);
        if (!empty($subscriptionHistories)) {
            foreach ($subscriptionHistories as $subscriptionHistory) {
                if ($subscriptionHistory->getSubscriptionPlan() instanceof SubscriptionPlan && $subscriptionHistory->getProperty() instanceof Property) {
                    $histories['propertyPlans'][] = $paymentService->formatPropertySubscriptionDetails($subscriptionHistory, $this->locale);
                } elseif ($subscriptionHistory->getCompanyPlan() instanceof CompanySubscriptionPlan && $subscriptionHistory->getUser() instanceof UserIdentity) {
                    $histories['companyPlans'][] = $paymentService->formatCompanySubscriptionDetails($subscriptionHistory, $this->locale);
                }
            }
        }
        $data = $generalUtility->handleSuccessResponse('fetchedSuccessfully', $histories);
        return $this->response($data);
    }

    /**
     * API end point to check whether the email exists or not
     *
     * # Request
     * In request body, system expects object details as JSON.
     * ## Example request to validate an email
     *       {
     *           "email": "hellenkeller@example.com",
     *           "isRegisterUser": true/false
     *       }
     *
     * ## Success response ##
     *
     *      {
     *           "data": [],
     *           "error": false,
     *           "message": "noEmailExists"
     *       }
     * ## Failed response ##
     *
     *      {
     *          "error": true,
     *          "message": "invalidEmailOrEmailExists"
     *      }
     *
     * @OA\Response(
     *     response=200,
     *     description="No email exists",
     * ),
     * @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     * ),
     * @OA\Response(
     *     response="401",
     *     description="User not authenticated"
     * ),
     * @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     * ),
     * @Operation(
     *     summary="API end point to check whether the email exists or not",
     *     @Security(name="Bearer")
     * )
     * @OA\Tag(name="User")
     * @param Request $request
     * @param ValidationUtility $validationUtility
     * @param GeneralUtility $generalUtility
     * @return View
     * @Route("/check/email-exists", name="balu_check_email_exists", methods={"POST"})
     */
    public function checkEmailExists(Request $request, ValidationUtility $validationUtility, GeneralUtility $generalUtility): View
    {
        $email = $request->request->get('email');
        $isUserRegistration = $request->request->get('isRegisterUser');
        $data = $generalUtility->handleFailedResponse('invalidEmailOrEmailExists');
        $emailExists = $validationUtility->checkEmailAlreadyExists($email);
        if ((!is_null($email) || $email !== '') && !$emailExists) {
            $data = $generalUtility->handleSuccessResponse('noEmailExists');
        } elseif ($isUserRegistration && $emailExists) {
            $em = $this->doctrine->getManager();
            $data = [];
            $user = $em->getRepository(UserIdentity::class)->findOneByEmail($email);
            if ($user instanceof UserIdentity) {
                $data['email'] = $email;
                $data['isGuestUser'] = $user->getIsGuestUser();
            }
            $data = $generalUtility->handleSuccessResponse('emailExists', $data);
        }
        return $this->response($data);
    }

    /**
     * API end point to update user data on registration
     *
     * # Request
     * In request body, system expects following data.
     * ## Example request to update user settings
     *      {
     *          "email": "hellenkeller@example.com",
     *          "password" : "hellen",
     *          "confirmPassword": "hellen",
     *          "firstName": "Hellen",
     *          "lastName": "Keller",
     *          "dob": "1995-12-22",
     *          "companyName": "Company name",
     *          "street": "Street name",
     *          "streetNumber": "Street number",
     *          "city": "city name",
     *          "zipCode": "201212",
     *          "mobile": "0123456789",
     *          "country": "India",
     *          "countryCode": "IN",
     *          "website": "http://example.com",
     *          "isPolicyAccepted": true,
     *          "role": "owner",
     *          "language": 'en',
     *          "damage": '1ebf4ee2-c332-644c-b170-4793878d25b4',
     *          "coverImage": '1ebf4ee2-c332-644c-b170-4793878d25b4'
     *      }
     * # Response
     * ## Success response ##
     *      {
     *          "error": false,
     *          "data": {
     *             "id": "1ebf4ee2-c332-644c-b170-4793878d25b4",
     *             "email": "hellenkeller@example.com",
     *             "firstName": "Hellen",
     *             "lastName": "Keller"
     *           },
     *          "message": "User registered successful"
     *      }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *         "data": {
     *            "email": "This value should not be blank.",
     *            "firstName": "This value should not be blank.",
     *            "lastName": "This value should not be blank.",
     *           },
     *         "error": true,
     *         "message": "Mandatory fields are missing"
     *      },
     *      {
     *        "data": "No data provided",
     *        "error": true,
     *        "message": "User already exists"
     *      },
     *      {
     *        "data": "No data provided",
     *        "error": true,
     *        "message": "Password Mismatch"
     *      }
     * @Route("/update", name="balu_user_register_update", methods={"POST"})
     * @Operation(
     *      tags={"User"},
     *      summary="API end point to update user data on registration.",
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               @OA\Property(property="email", type="string", default="", example="hellenkeller@example.com"),
     *               @OA\Property(property="password", type="string", default="", example="hellen123"),
     *               @OA\Property(property="confirmPassword", type="string", default="", example="hellen123"),
     *               @OA\Property(property="firstName", type="string", default="", example="Hellen"),
     *               @OA\Property(property="lastName", type="string", default="", example="Keller"),
     *               @OA\Property(property="dob", type="string", default="", example="1995-12-22"),
     *               @OA\Property(property="companyName", type="string", default="", example="Hykon"),
     *               @OA\Property(property="street", type="string", default="", example="Las vegas"),
     *               @OA\Property(property="streetNumber", type="string", default="", example="No. 18, LV"),
     *               @OA\Property(property="city", type="string", default="", example="New York city"),
     *               @OA\Property(property="zipCode", type="string", default="", example="20325"),
     *               @OA\Property(property="mobile", type="string", default="", example="0123456789"),
     *               @OA\Property(property="country", type="string", default="", example="India"),
     *               @OA\Property(property="countryCode", type="string", default="", example="IN"),
     *               @OA\Property(property="website", type="string", default="", example="20325"),
     *               @OA\Property(property="isPolicyAccepted", type="boolean", default="", example="true"),
     *               @OA\Property(property="role", type="string", default="", example="owner/propertyAdmin/company"),
     *               @OA\Property(property="landLine", type="string", default="", example="+1255487844"),
     *               @OA\Property(property="latitude", type="string", default="", example="10.00000"),
     *               @OA\Property(property="longitude", type="string", default="", example="7.25458"),
     *               @OA\Property(property="coverImage", type="string", default="", example="1ee2c428-cf40-652a-ac71-0242ac120003"),
     *               @OA\Property(property="damage", type="string", default="", example="1ee2c428-cf40-652a-ac71-0242ac120003"),
     *           )
     *       )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returned on successful registration"
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Returned when request is not valid"
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Internal Error"
     *     )
     *)
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param RegistrationService $registrationService
     * @param ValidationUtility $validationUtility
     * @param UserService $userService
     * @param CompanyService $companyService
     * @param DamageService $damageService
     * @param UserPasswordHasherInterface $passwordHasher
     * @param ContainerUtility $containerUtility
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function update(Request $request, GeneralUtility $generalUtility, RegistrationService $registrationService,
                           ValidationUtility $validationUtility, UserService $userService, CompanyService $companyService, DamageService $damageService,
                           UserPasswordHasherInterface $passwordHasher, ContainerUtility $containerUtility, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $email = $request->request->get('email');
        $userObj = $em->getRepository(User::class)->findOneBy(['property' => $email]);
        if ($validationUtility->checkEmailAlreadyExists($email) &&
            ($request->request->has('damage') && $request->request->get('damage') === "" ||
                $userObj instanceof User && !$userObj->getUserIdentity()->getIsGuestUser())) {
            return $this->response($generalUtility->handleFailedResponse('userExists'));
        }
        if (!$this->securityService->checkPasswordMatch($request)) {
            return $this->response($generalUtility->handleFailedResponse('passwordMisMatch'));
        }
        $userIdentity = $userObj->getUserIdentity();
        $role = $request->request->get('role');
        $form = $this->createNamedForm(CompanyRegistrationType::class, $userIdentity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->beginTransaction();
            try {
                $userIdentity->setLanguage($this->locale);
                $params = $registrationService->updateUser($form, $userIdentity, $passwordHasher, $companyService, $damageService);
                $containerUtility->sendEmailConfirmation($userIdentity, ucfirst($role) . 'Registration',
                    $this->locale, $role . 'ConfirmRegistration',
                    $request->request->get('role'),
                    $params,
                    false,
                    false, [], false, true);
                $em->flush();
                $em->commit();
                $data = $generalUtility->handleSuccessResponse('userRegisteredSuccessful',
                    $userService->getUserData($userIdentity));
            } catch (InvalidPasswordException | \Exception $e) {
                $em->rollback();
                $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
                $data = $generalUtility->handleFailedResponse($e->getMessage());
            }
        } else {
            $data = $generalUtility->handleFailedResponse('formError', 400, $this->getErrorsFromForm($form));
        }
        return $this->response($data);
    }
}
