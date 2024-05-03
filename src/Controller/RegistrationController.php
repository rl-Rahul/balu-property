<?php

/**
 * This file is part of the Balu 2.0 Project package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserIdentity;
use App\Form\CompanyRegistrationType;
use App\Form\GuestProfileRegistrationType;
use App\Service\CompanyService;
use App\Service\DamageService;
use App\Service\RegistrationService;
use App\Service\UserService;
use App\Utils\Constants;
use App\Utils\ContainerUtility;
use App\Utils\GeneralUtility;
use App\Utils\ValidationUtility;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * RegistrationController
 *
 * Controller to manage registration actions.
 *
 * @package         Balu Property App2
 * @subpackage      App
 * @author          Rahul<rahul.rl@pitsolutions.com>
 * @Route("/register")
 */
final class RegistrationController extends BaseController
{
    /**
     * API end point to user registration.
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
     * @Route("/user", name="balu_register", methods={"POST"})
     * @Operation(
     *      tags={"Registration"},
     *      summary="API end point to registration.",
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
     * @param DamageService $damageService
     * @param CompanyService $companyService
     * @param UserPasswordHasherInterface $passwordHasher
     * @param ContainerUtility $containerUtility
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function register(Request $request, GeneralUtility $generalUtility, RegistrationService $registrationService,
                             ValidationUtility $validationUtility, UserService $userService, DamageService $damageService, CompanyService $companyService,
                             UserPasswordHasherInterface $passwordHasher, ContainerUtility $containerUtility, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $email = $request->request->get('email');
        if ($validationUtility->checkEmailAlreadyExists($email)) {
            return $this->response($generalUtility->handleFailedResponse('userExists'));
        }
        if (!$this->securityService->checkPasswordMatch($request)) {
            return $this->response($generalUtility->handleFailedResponse('passwordMisMatch'));
        }
        $userIdentity = new UserIdentity();
        $role = $request->request->get('role');
        $formType = "App\\Form\\" . ucfirst($role) . 'RegistrationType';
        $form = $this->createNamedForm($formType, $userIdentity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->beginTransaction();
            try {
                $userIdentity->setLanguage($this->locale);
                if ($form->has('damage') && $form->get('damage')->getData() !== "") {
                    $params = $registrationService->updateUser($form, $userIdentity, $passwordHasher, $companyService, $damageService, true);
                } else {
                    $params = $registrationService->registerUser($form, $userIdentity, $passwordHasher);
                }
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

    /**
     * API end point to guest user registration
     *
     * # Request
     * ## Example request for social connection
     *      {
     *          "email" : "guestuser@example.com",
     *          "companyName": "company name",
     *          "street": "street name",
     *          "streetNumber": "12345",
     *          "phone": "1236547890"
     *      }
     * ## Success response ##
     *       {
     *               "data": {
     *                   "token_type": "Bearer",
     *                   "expires_in": 28799,
     *                   "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiJmYWFhOTkxNGJmNGVl.....",
     *                   "refresh_token": "def5020099f7f2f760465291a1dc1163fb3877d3b5423a584346532b905f......"
     *               },
     *               "error": false,
     *               "message": "Login Successful"
     *       }
     * @Route("/guest/user", name="balu_guest_registration", methods={"POST"})
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns access and refresh token of a user",
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
     * @OA\Tag(name="Security")
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param RegistrationService $registrationService
     * @param UserPasswordHasherInterface $passwordHasher
     * @param ContainerUtility $containerUtility
     * @return View
     */
    public function guestRegistration(Request $request, GeneralUtility $generalUtility, RegistrationService $registrationService,
                                      UserPasswordHasherInterface $passwordHasher, ContainerUtility $containerUtility): View
    {
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            $update = true;
            $user = $em->getRepository(User::class)->findOneBy(['property' => $request->request->get('email')]);
            $userIdentity = $em->getRepository(UserIdentity::class)->findOneBy(['user' => $user]);
            if (!$userIdentity instanceof UserIdentity) {
                $update = false;
                $userIdentity = new UserIdentity();
            }
            $form = $this->createNamedForm(GuestProfileRegistrationType::class, $userIdentity);
            $request->request->set('firstName', 'Guest');
            $request->request->set('lastName', 'User');
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $userIdentity = $registrationService->guestUserRegistration($form, $userIdentity, $passwordHasher, $update);
                if (!$userIdentity instanceof UserIdentity) {
                    throw new UnsupportedUserException('userRegistrationFailed');
                }
            }
            $registrationService->checkAndSaveOtp($userIdentity);
            $containerUtility->sendEmailConfirmation($userIdentity, 'GuestRegistration', $this->locale,
                'guestUserVerification', Constants::GUEST_ROLE);
            $data = $generalUtility->handleSuccessResponse('guestUserRegistrationSuccessful');
            $em->flush();
            $em->commit();
        } catch (InvalidArgumentException | AccessDeniedException | ValidationFailedException | \Exception $e) {
            $em->rollback();
            $data = $generalUtility->handleFailedResponse('validationFailed');
        }
        return $this->response($data);
    }

//    /**
//     * API end point to user registration.
//     *
//     * # Request
//     * In request body, system expects following data.
//     * ## Example request to update user settings
//     *      {
//     *          "email": "hellenkeller@example.com"
//     *      }
//     * # Response
//     * ## Success response ##
//     *      {
//     *          "error": false,
//     *          "data": [],
//     *          "message": "Confirmation mail sent successfully"
//     *      }
//     * ## Failed response ##
//     * ### due to validation error
//     *      {
//     *         "data": {
//     *            "email": "This value should not be blank.",
//     *           },
//     *         "error": true,
//     *         "message": "Mandatory fields are missing"
//     *      }
//     *
//     * @Route("/resend", name="balu_register_resend", methods={"POST"})
//     * @Operation(
//     *      tags={"Registration"},
//     *      summary="API end point to registration.",
//     *      @OA\RequestBody(
//     *       required=false,
//     *       @OA\MediaType(
//     *           mediaType="application/json",
//     *           @OA\Schema(
//     *               @OA\Property(property="email", type="string", default="", example="hellenkeller@example.com"),
//     *           )
//     *       )
//     *     ),
//     *     @OA\Response(
//     *         response="200",
//     *         description="Returned on successful registration"
//     *     ),
//     *     @OA\Response(
//     *         response="400",
//     *         description="Returned when request is not valid"
//     *     ),
//     *     @OA\Response(
//     *          response="401",
//     *          description="User not authenticated"
//     *     ),
//     *     @OA\Response(
//     *         response="500",
//     *         description="Internal Error"
//     *     )
//     *)
//     * @param Request $request
//     * @param GeneralUtility $generalUtility
//     * @param ContainerUtility $containerUtility
//     * @param ValidationUtility $validationUtility
//     * @param UserService $userService
//     * @return View
//     */
//    public function resendRegistrationEmail(Request $request, GeneralUtility $generalUtility,
//                                            ContainerUtility $containerUtility, ValidationUtility $validationUtility,
//                                            UserService $userService): View
//    {
//        $userInfo = ['email' => $request->request->get('email')];
//        $form = $this->createNamedForm(EmailType::class, $userInfo);
//        $form->handleRequest($request);
//        $data = $generalUtility->handleFailedResponse('emailNotExists');
//        if ($form->isSubmitted() && $form->isValid()) {
//            try {
//                $entityManager = $this->doctrine->getManager();
//                if ($oUser = $entityManager->getRepository(User::class)->findOneBy($userInfo)) {
//                    if ($oUser instanceof User && !empty($response = $validationUtility->validateUserStatus($oUser, 'register'))) {
//                        return $this->response($generalUtility->handleFailedResponse($response['statusMessage'], $response['statusCode']));
//                    }
//                    $containerUtility->sendEmailConfirmation($oUser, 'email_confirmation', $this->locale, 'emailConfirmation');
//                    $data = $generalUtility->handleSuccessResponse($userService->getUserData($oUser), 'resendMailSuccess');
//                }
//            } catch (\Exception $e) {
//                $data = $generalUtility->handleFailedResponse($e->getMessage());
//            }
//        }
//        return $this->response($data);
//    }
}