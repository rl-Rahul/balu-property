<?php

/**
 * This file is part of the PITS Project package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\DamageRequest;
use App\Entity\User;
use App\Entity\UserIdentity;
use App\Form\EmailType;
use App\Form\GuestProfileRegistrationType;
use App\Form\PasswordType;
use App\Service\CompanyService;
use App\Service\DamageService;
use App\Service\RegistrationService;
use App\Service\SecurityService;
use App\Service\UserService;
use App\Utils\Constants;
use App\Utils\ContainerUtility;
use App\Utils\GeneralUtility;
use App\Utils\ValidationUtility;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\View\View;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Constraints\Uuid as UuidConstraint;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;


/**
 * SecurityController
 *
 * Controller to manage secured user actions.
 *
 * @package         PITS
 * @subpackage      App
 * @author          Rahul<rahul.rl@pitsolutions.com>
 * @Route("/secured")
 */
final class SecurityController extends BaseController
{
    /**
     * @var UserPasswordHasherInterface $passwordHasher
     */
    private UserPasswordHasherInterface $passwordHasher;

    /**
     * @var ValidationUtility $validationUtility
     */
    private ValidationUtility $validationUtility;

    /**
     * SecurityController constructor.
     * @param ManagerRegistry $doctrine
     * @param UserPasswordHasherInterface $passwordHasher
     * @param RequestStack $request
     * @param TranslatorInterface $translator
     * @param ValidationUtility $validationUtility
     * @param ParameterBagInterface $parameterBag
     * @param SecurityService $securityService
     */
    public function __construct(ManagerRegistry $doctrine, UserPasswordHasherInterface $passwordHasher,
                                RequestStack $request, TranslatorInterface $translator, ValidationUtility $validationUtility,
                                ParameterBagInterface $parameterBag, SecurityService $securityService)
    {
        parent::__construct($request, $translator, $doctrine, $parameterBag, $securityService);
        $this->passwordHasher = $passwordHasher;
        $this->validationUtility = $validationUtility;
    }

    /**
     * API end point to login and refresh token
     *
     * # Request
     * In request body, system expects email of the user as username and password.
     * ## Example request to Login request
     *      {
     *          "username" : "test@yopmail.com",
     *          "password" : "password",
     *          "deviceId" : "Zwesdfdswedfsdfs"
     *      }
     * In request body, system expects grant_type and refresh_token.
     * ## Example request to Refresh token
     *      {
     *          "username" : "test@yopmail.com",
     *          "grant_type" : "refresh_token",
     *          "refresh_token" : "def502005097fef8784106f026b8cdf561c683c5273ea222a0d5bff3dbbae......"
     *      }
     * # Response
     * ## Success response ##
     *      {
     *          "error": false,
     *          "data": {
     *              "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9........",
     *              "expires_in": 3600,
     *              "token_type": "Bearer",
     *              "refresh_token": "def50200b166884cbe45867bc6330565f37b763d69d752a91922eadc........."
     *              "is_first_login": false
     *          },
     *          "message": "Login Successful"
     *      }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *        "data": "No data provided",
     *        "error": true,
     *        "message": "Invalid grant or password not valid"
     *      },
     *      {
     *        "data": "No data provided",
     *        "error": true,
     *        "message": "User \"test@example.com\" not found."
     *      },
     *      {
     *        "data": "No data provided",
     *        "error": true,
     *        "message": "User not enabled"
     *      }
     * @Route("/login", name="balu_login", methods={"POST"})
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
     * @OA\Response(
     *     response="422",
     *     description="User not found"
     * )
     * @Operation(
     *     summary="API end point to login.",
     *     @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               type="object",
     *               @OA\Property(
     *                  property="username",
     *                  type="object",
     *                  default="",
     *                  example="test@yopmail.com"
     *              ),
     *              @OA\Property(
     *                  property="password",
     *                  type="object",
     *                  default="",
     *                  example="password"
     *              ),
     *              @OA\Property(
     *                  property="deviceId",
     *                  type="string",
     *                  default="",
     *                  example=""
     *              )
     *           )
     *       )
     *     ),
     * )
     * @OA\Tag(name="Security")
     * @param Request $request
     * @param ResponseFactoryInterface $responseFactory
     * @param GeneralUtility $generalUtility
     * @param DamageService $damageService
     * @param CompanyService $companyService
     * @return View
     */
    public function login(Request $request, ResponseFactoryInterface $responseFactory, GeneralUtility $generalUtility,
                          DamageService $damageService, CompanyService $companyService): View
    {
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $psrRequest = $psrHttpFactory->createRequest($request);
        $data = $this->securityService->loginProcess($request, $psrRequest, $responseFactory, $generalUtility,
            $damageService, $companyService, null, false, $this->locale);
        return $this->response($data);
    }

    /**
     * API end point to self change password after login.
     *
     * # Request
     * In request body, system expects new password, current password, and confirm password.
     * ## Example request to update user password
     *      {
     *          "newPassword": "1324"
     *          "currentPassword": "1324",
     *          "confirmPassword": "1324"
     *      }
     * # Response
     * ## Success response ##
     *      {
     *          "data": "true",
     *          "error": false,
     *          "message": "Password changed successfully"
     *      }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *        "data": "No data provided",
     *        "error": true,
     *        "message": "The password must contain numbers and letters."
     *      }
     *
     * @Route("/change-password", name="balu_change_password", methods={"POST"})
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
     * @OA\Response(
     *     response="422",
     *     description="User not found"
     * )
     * @Operation(
     *     summary="API end point to change user password",
     *     @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               type="object",
     *               @OA\Property(
     *                  property="currentPassword",
     *                  type="string",
     *                  default="",
     *                  example="12345"
     *              ),
     *              @OA\Property(
     *                  property="newPassword",
     *                  type="string",
     *                  default="",
     *                  example="1234"
     *              ),
     *              @OA\Property(
     *                  property="confirmPassword",
     *                  type="string",
     *                  default="",
     *                  example="1234"
     *              )
     *           )
     *       )
     *     )
     * )
     * @OA\Tag(name="Security")
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param ValidationUtility $validationUtility
     * @return View
     */
    public function changePassword(Request $request, GeneralUtility $generalUtility, ValidationUtility $validationUtility, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $data = $generalUtility->handleFailedResponse('wrongPasswordCriterion');
        $form = $this->createNamedForm(PasswordType::class, ['current' => true]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            try {
                $em->beginTransaction();
                $user = $this->getUser()->getUser();
                if (!$this->passwordHasher->isPasswordValid($user, $form->get('currentPassword')->getData())) {
                    throw new InvalidPasswordException('oldPasswordMismatch');
                }
                if (!$validationUtility->isValidPassword($form, $user)) {
                    throw new InvalidPasswordException('invalidPassword');
                }
                $user->setPassword($this->passwordHasher->hashPassword($user, $form->get('confirmPassword')->getData()));
                $em->flush();
                $em->commit();
                $data = $generalUtility->handleSuccessResponse('passwordChangeSuccess');
            } catch (\Exception $e) {
                $em->rollback();
                $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
                $data = $generalUtility->handleFailedResponse($e->getMessage());
            }
        }
        return $this->response($data);
    }

    /**
     * API end point to reset password from forgot mail.
     *
     * # Request
     * In request body, system expects new password, token and confirm password.
     * ## Example request to set new user password
     *      {
     *          "newPassword": "1324"
     *          "confirmPassword": "1324"
     *          "token": "aGJUUE5wZUFVcW1waExvcUMxdi9NNG5rbU4vWm5FWjVQbVVIZW1BcXd2dz06Okc1w0XfYb1oWJR2F1gU"
     *      }
     * # Response
     * ## Success response ##
     *      {
     *          "error": false,
     *          "data": "No data provided",
     *          "message": "Password reset successfully"
     *      }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *        "data": "No data provided",
     *        "error": true,
     *        "message": "Password reset failed"
     *      }
     *
     * @Route("/reset-password", name="balu_reset_password", methods={"POST"})
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
     * @OA\Response(
     *     response="422",
     *     description="User not found"
     * )
     * @Operation(
     *     summary="API end point to reset user password",
     *     @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               type="object",
     *               @OA\Property(
     *                  property="newPassword",
     *                  type="object",
     *                  default="",
     *                  example="1234"
     *              ),
     *              @OA\Property(
     *                  property="confirmPassword",
     *                  type="object",
     *                  default="",
     *                  example="1234"
     *              ),
     *              @OA\Property(
     *                  property="token",
     *                  type="object",
     *                  default="",
     *                  example="aGJUUE5wZUFVcW1waExvcUMxdi9NNG5rbU4vWm5FWjVQbVVIZW1BcXd2dz06Okc1w0XfYb1oWJR2F1gU"
     *              )
     *           )
     *       )
     *     ),
     * )
     * @OA\Tag(name="Security")
     * @param Request $request
     * @param ContainerUtility $containerUtility
     * @param GeneralUtility $generalUtility
     * @param ValidationUtility $validationUtility
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function resetPassword(Request $request, ContainerUtility $containerUtility,
                                  GeneralUtility $generalUtility, ValidationUtility $validationUtility,
                                  LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $form = $this->createNamedForm(PasswordType::class, ['reset' => true]);
        $form->handleRequest($request);
        $data = $generalUtility->handleFailedResponse('resetPasswordFail');
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->beginTransaction();
            try {
                $oUser = $em->getRepository(User::class)->findOneBy(['confirmationToken' => $request->request->get('token')]);
                $token = $containerUtility->validateToken($request);
                if ((!$oUser instanceof User && $oUser->getProperty() !== $token->getProperty()) ||
                    (!$validationUtility->isValidPassword($form) || !$oUser->getIsTokenVerified())) {
                    throw new CustomUserMessageAuthenticationException('invalidToken');
                }
                $oUser->setPassword($this->passwordHasher->hashPassword($oUser, $form->get('newPassword')->getData()));
                $oUser->setConfirmationToken(null);
                $em->flush();
                $em->commit();
                $data = $generalUtility->handleSuccessResponse('passwordResetSuccess');
            } catch (CustomUserMessageAuthenticationException | \Exception $e) {
                $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
                $data = $generalUtility->handleFailedResponse($e->getMessage());
                $em->rollback();
            }
        }
        return $this->response($data);
    }

    /**
     * API end point to send forgot password email.
     *
     * # Request
     * In request body, system expects email Id of the user .
     * ## Example request to update user settings
     *      {
     *          "email": "hellenkeller@example.com"
     *      }
     * # Response
     * ## Success response ##
     *      {
     *          "error": false,
     *          "data": "false",
     *          "message": "You will be receiving the reset password email with the instructions in a few minutes"
     *      }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *        "data": "No data provided",
     *        "error": true,
     *        "message": "This email id does not Exists"
     *      }
     *
     * @Route("/forgot-password", name="balu_forgot_password", methods={"POST"})
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
     * @OA\Response(
     *     response="422",
     *     description="User not found"
     * )
     * @Operation(
     *     summary="API end point to send forgot password link.",
     *     @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               type="object",
     *               @OA\Property(
     *                  property="email",
     *                  type="object",
     *                  default="",
     *                  example="test@yopmail.com"
     *              )
     *           )
     *       )
     *     ),
     * )
     * @OA\Tag(name="Security")
     * @param Request $request
     * @param ContainerUtility $containerUtility
     * @param GeneralUtility $generalUtility
     * @param UserService $userService
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function forgotPassword(Request $request, ContainerUtility $containerUtility,
                                   GeneralUtility $generalUtility, UserService $userService,
                                   LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $userInfo = ['property' => $request->request->get('email')];
        $form = $this->createNamedForm(EmailType::class, $userInfo);
        $form->handleRequest($request);
        $data = $generalUtility->handleFailedResponse('emailNotExists');
        if ($form->isSubmitted() && $form->isValid() && empty($this->getErrorsFromForm($form))) {
            try {
                $em = $this->doctrine->getManager();
                $oUser = $em->getRepository(User::class)->findOneBy($userInfo);
                $userIdentity = $em->getRepository(UserIdentity::class)->findOneBy(['user' => $oUser]);
                if ($userIdentity instanceof UserIdentity) {
                    $this->validationUtility->validateUserStatus($userIdentity, 'forgotPassword');
                    $containerUtility->sendEmailConfirmation($userIdentity, 'ResetPassword', $this->locale, 'passwordReset', null, null, true);
                    $data = $generalUtility->handleSuccessResponse('resetMailSuccess', $userService->getUserData($userIdentity));
                    $oUser->setPasswordRequestedAt(new \DateTime());
                    $em->flush();
                }
            } catch (UserNotFoundException | AccessDeniedException
            | CustomUserMessageAuthenticationException | CustomUserMessageAccountStatusException
            | \Exception $e) {
                $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
                $data = $generalUtility->handleFailedResponse($e->getMessage());
            }
        }
        return $this->response($data);
    }

    /**
     * API end point to verify email token.
     *
     * # Request
     * In request body, system expects token of the user. action parameter specifies whether the token check for email verification or password reset
     *
     * For email verification action = register
     *
     * For password reset action = password
     * ## Example request to verify token
     *      {
     *          "token": "bGtWaUlrOTNIVXViTHZKazVqTXdlNjRTSXRCbjkwSmdRVmQybFpMTW5ZND06OiPqZUaHHJjwy5LTaZbg"
     *      }
     * # Response
     * ## Success response ##
     *      {
     *          "error": false,
     *          "data": "[]",
     *          "message": "Verification Success"
     *          },
     *      }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *           "data: "No data provided"
     *           "error": true,
     *           "message": "Invalid Token"
     *     }
     *
     * @Route("/verify-token/{action}", defaults={"action" = null}, name="balu_verify_token", methods={"POST"})
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
     * @OA\Response(
     *     response="422",
     *     description="User not found"
     * )
     * @Operation(
     *     summary="API end point to verify token .",
     *     @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               type="object",
     *               @OA\Property(
     *                  property="token",
     *                  type="object",
     *                  default="",
     *                  example="bGtWaUlrOTNIVXViTHZKazVqTXdlNjRTSXRCbjkwSmdRVmQybFpMTW5ZND06OiPqZUaHHJjwy5LTaZbg"
     *              )
     *           )
     *       )
     *     ),
     * )
     * @OA\Tag(name="Security")
     * @param Request $request
     * @param string $action
     * @param ContainerUtility $containerUtility
     * @param GeneralUtility $generalUtility
     * @param UserService $userService
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function verifyToken(Request $request, string $action, ContainerUtility $containerUtility,
                                GeneralUtility $generalUtility, UserService $userService,
                                LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $data = $generalUtility->handleFailedResponse('invalidToken');
        try {
            if (false === $request->request->has('token')) {
                throw new TokenNotFoundException('tokenKeyExpected');
            }
            if (is_null($authorizationToken = $request->request->get('token'))) {
                throw new TokenNotFoundException('invalidToken');
            }
            if ($oUser = $em->getRepository(User::class)->findOneBy(['confirmationToken' => $authorizationToken])) {
                $token = $containerUtility->validateToken($request);
                if ($oUser instanceof User && $oUser->getProperty() === $token->getProperty()) {
                    if ($action === 'register') {
                        $userService->getUserIdentity($oUser)->setEnabled(true);
                        $oUser->setConfirmationToken(null);
                        $data = $generalUtility->handleSuccessResponse('userVerificationSuccess');
                    } else {
                        if ($oUser->getIsTokenVerified() === true) {
                            throw new TokenNotFoundException('invalidToken');
                        }
                        $oUser->setIsTokenVerified(true);
                        $data = $generalUtility->handleSuccessResponse('linkVerificationSuccess', ['token' => $request->request->get('token')]);
                    }
                    $em->flush();
                }
            }
        } catch (TokenNotFoundException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }
        return $this->response($data);
    }

    /**
     * API end point to logout user
     *
     * # Request
     *
     * If logOutEverywhere is true, the user will be logged out from all devices
     *
     * ## Example request to Logout user from specific device
     *      {
     *          "deviceId" : "1ec7c797-b651-6d5a-8f5c-00155d01d845"
     *      }
     * ## Example request to Logout user from all devices
     *      {
     *          "logOutEverywhere" : true
     *      }
     * # Response
     * ## Success response ##
     *      {
     *          "error": false,
     *          "message": "Logout successful"
     *      }
     * ## Failed response ##
     * ### due to authentication error
     *      {
     *        "data": "No data provided",
     *        "error": true,
     *        "message": "The resource server rejected the request."
     *      }
     * @Route("/logout", name="balu_logout", methods={"POST"})
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
     *     summary="API end point to logout.",
     *     @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               type="object",
     *              @OA\Property(
     *                  property="deviceId",
     *                  type="string",
     *                  default="",
     *                  example=""
     *              ),
     * *             @OA\Property(
     *                  property="logOutEverywhere",
     *                  type="bool",
     *                  default="false",
     *                  example="false"
     *              )
     *           )
     *       )
     *     ),
     * )
     * @OA\Tag(name="Security")
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param UserService $userService
     * @return View
     * @throws \Exception
     */
    public function logout(Request $request, GeneralUtility $generalUtility, UserService $userService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $data = $generalUtility->handleFailedResponse('logoutFailed');
        try {
            if ($this->getUser() instanceof UserIdentity) {
                $em = $this->doctrine->getManager();
                $logoutEverywhere = (null !== $request->request->get('logOutEverywhere')) ? $request->request->get('logOutEverywhere') : false;
                $userService->revokeTokens($this->getUser(), $this->get('security.token_storage')->getToken(), $logoutEverywhere);
                $userService->removeUserDeviceDetails($this->getUser(), $logoutEverywhere, $request->request->get('deviceId'));
                $em->flush();
                $data = $generalUtility->handleSuccessResponse('logoutSuccess');
            }
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to get currently logged in user roles.
     *
     * # Request
     * ### api/2.0/secured/user/roles
     * # Response
     * ## Success response ##
     * {
     *      "data": [
     *            {
     *                  "roleKey": "owner",
     *                  "name": "Owner"
     *             },
     *             {
     *                  "roleKey": "propertyAdmin",
     *                  "name": "Property Administrator"
     *              }
     *              ],
     *      "error": false,
     *       "message": "dataFetchSuccess"
     * }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "formError"
     *       }
     * @Route("/user/roles", name="balu_get_current_user_roles", methods={"GET"})
     * @Operation(
     *      tags={"Security"},
     *      summary="API end point to get currently logged in user roles",
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
     * @param UserService $userService
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @return View
     */
    public function roles(GeneralUtility $generalUtility, UserService $userService, LoggerInterface $requestLogger, Request $request): View
    {
        $curDate = new \DateTime('now');
        try {
            if (!$this->getUser() instanceof UserIdentity) {
                throw new \Exception('invalidUser', 400);
            }
            $data = $generalUtility->handleSuccessResponse('dataFetchSuccess', $userService->getUserRoles($this->getUser(), $this->currentRole));
        } catch (InvalidPasswordException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to web interface login only
     *
     * # Request
     * ## Example request for social connection
     *      {
     *          "userToken" : "AQBpCa9D9oRAVi7I3PrUkzlSrQAEyFPeojOY9_XlZglAQBpCa9D9oRAVi7I3PrU......."
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
     * @Route("/web/login", name="balu_web_login", methods={"POST"})
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
     * @param ServerRequestInterface $serverRequest
     * @param ResponseFactoryInterface $responseFactory
     * @param SecurityService $securityService
     * @param GeneralUtility $generalUtility
     * @param ContainerUtility $containerUtility
     * @param DamageService $damageService
     * @param CompanyService $companyService
     * @return View
     */
    public function webLogin(Request $request, ServerRequestInterface $serverRequest,
                             ResponseFactoryInterface $responseFactory, SecurityService $securityService,
                             GeneralUtility $generalUtility, ContainerUtility $containerUtility,
                             DamageService $damageService, CompanyService $companyService): View
    {
        $em = $this->doctrine->getManager();
        try {
            $email = $containerUtility->decryptEmail($request->request->get('userToken'));
            $user = $em->getRepository(User::class)->findOneBy(['property' => $email]);
            if (!$user instanceof User) {
                throw new AccessDeniedException('invalidUser');
            }
            $replaceBy = [
                'username' => $user->getProperty(),
                'password' => md5('password')
            ];
            $request->request->replace($replaceBy);
            $data = $securityService->loginProcess($request, $serverRequest, $responseFactory, $generalUtility, $damageService, $companyService);
        } catch (InvalidArgumentException | AccessDeniedException $e) {
            $data = $this->response($generalUtility->handleFailedResponse($e->getMessage()));
        } catch (ValidationFailedException | \Exception $e) {
            $data = $this->response($generalUtility->handleFailedResponse('validationFailed'));
        }
        return $this->response($data);
    }

    /**
     * API end point to verify guest user email.
     *
     * # Request
     * In request body, system expects OTP which sent to the email and this OTP needs to validate against the user
     *
     * ## Example request to verify token
     *      {
     *          "token": "846676",
     *          "email": "guestuser@example.com"
     *      }
     * # Response
     * ## Success response ##
     *      {
     *          "error": false,
     *          "data": "[]",
     *          "message": "Verification Success"
     *          },
     *      }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *           "data: "No data provided"
     *           "error": true,
     *           "message": "Invalid Token"
     *     }
     *
     * @Route("/verify-guest-user", name="balu_verify_guest_user", methods={"POST"})
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
     * @OA\Response(
     *     response="422",
     *     description="User not found"
     * )
     * @Operation(
     *     summary="API end point to verify guest user email",
     *     @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               type="object",
     *               @OA\Property(
     *                  property="token",
     *                  type="object",
     *                  default="",
     *                  example="846676"
     *              )
     *           )
     *       )
     *     ),
     * )
     * @OA\Tag(name="Security")
     * @param Request $request
     * @param ResponseFactoryInterface $responseFactory
     * @param SecurityService $securityService
     * @param CompanyService $companyService
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param DamageService $damageService
     * @return View
     */
    public function verifyGuestUser(Request $request,
                                    ResponseFactoryInterface $responseFactory, SecurityService $securityService, CompanyService $companyService,
                                    GeneralUtility $generalUtility, LoggerInterface $requestLogger, DamageService $damageService): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            $authorizationCode = $request->request->get('token');
            if (is_null($authorizationCode) || $authorizationCode === '') {
                throw new TokenNotFoundException('invalidToken');
            }
            $email = $request->request->get('email');
            $userIdentity = $em->getRepository(UserIdentity::class)->findOneByAuthCode(['authCode' => $authorizationCode, 'email' => $email]);
            if (!$userIdentity instanceof UserIdentity) {
                throw new TokenNotFoundException('invalidToken');
            }
            $userIdentity->setEnabled(true);
            $userIdentity->setAuthCode(null);
            $em->flush();
            if ($request->request->has('damage') && $request->request->get('damage') !== '') {
                $damageService->registerDamageRequestIfNotExists($request->request->get('damage'), $userIdentity, $email);
            }
            $damageRequests = $em->getRepository(DamageRequest::class)->findBy(['companyEmail' => $email]);
            if (!empty($damageRequests)) {
                foreach ($damageRequests as $dRequest) {
                    $dRequest->setCompany($userIdentity);
                }
                $em->flush();
            }
            $replaceBy = [
                'username' => $userIdentity->getUser()->getProperty(),
                'password' => md5('password')
            ];
            $request->request->replace($replaceBy);
            $psr17Factory = new Psr17Factory();
            $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
            $psrRequest = $psrHttpFactory->createRequest($request);
            $data = $this->securityService->loginProcess($request, $psrRequest, $responseFactory, $generalUtility,
                $damageService, $companyService, null, true, $this->locale);
            $em->commit();
        } catch (TokenNotFoundException | \Exception $e) {
            $em->rollback();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }
        return $this->response($data);
    }

    /**
     * API end point to validate guest user email address
     *
     * # Request
     * ## Example request to validate guest user
     *      {
     *          "user" : "1ec34f72-e1af-6fde-b32b-df4933035eec"
     *      }
     * ## Success response ##
     *       {
     *               "data": {
     *                   "email": "email@example.com"
     *               },
     *               "error": false,
     *               "message": "Login Successful"
     *       }
     * @Route("/validate/guest-user", name="balu_validate_guest_user", methods={"POST"})
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
     * @param RegistrationService $registrationService
     * @param GeneralUtility $generalUtility
     * @param ContainerUtility $containerUtility
     * @return View
     */
    public function validateGuestUser(Request $request, RegistrationService $registrationService,
                                      GeneralUtility $generalUtility, ContainerUtility $containerUtility): View
    {
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            $userPublicId = $request->request->get('user');
            if (is_null($userPublicId)) {
                throw new UserNotFoundException('invalidUserToken');
            }
            $user = $em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $userPublicId]);
            if (!$user instanceof UserIdentity) {
                throw new UserNotFoundException('invalidUser');
            }
            $registrationService->checkAndSaveOtp($user);
            $containerUtility->sendEmailConfirmation($user, 'GuestUserValidation', $this->locale,
                'guestUserValidation', Constants::GUEST_ROLE);
            $data = $generalUtility->handleSuccessResponse('guestUserValidationMailSend', ['email' => $user->getUser()->getProperty()]);
            $em->flush();
            $em->commit();
        } catch (InvalidArgumentException | AccessDeniedException | ValidationFailedException | \Exception $e) {
            $em->rollback();
            $data = $generalUtility->handleFailedResponse('validationFailed');
        }
        return $this->response($data);
    }
}
