<?php

/**
 * This file is part of the Balu Property package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\UserIdentity;
use App\Form\PropertyAdminByOwnerRegistrationType;
use App\Service\RegistrationService;
use App\Service\UserService;
use App\Utils\ContainerUtility;
use App\Utils\ValidationUtility;
use Nelmio\ApiDocBundle\Annotation\Operation;
use App\Utils\GeneralUtility;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;
use App\Service\CompanyService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

/**
 * AdministratorController
 *
 * Controller to manage administrator related actions.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 * @Route("/administrator")
 */
final class AdministratorController extends BaseController
{
    /**
     * API end point to administration registration.
     *
     * # Request
     * In request body, system expects following data.
     * ## Example request to update user settings
     *      {
     *          "email": "hellenkeller@example.com",
     *          "firstName": "Hellen",
     *          "lastName": "Keller",
     *          "administratorName": "Administrator",
     *          "mobile": "0123456789",
     *          "landLine": "0123456789",
     *          "website": "http://example.com",
     *          "role": "propertyAdmin",
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
     * @Route("/create", name="balu_administrator_create", methods={"POST"})
     * @Operation(
     *      tags={"Administrator"},
     *      summary="API end point to administrator registration.",
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               @OA\Property(property="email", type="string", default="", example="hellenkeller@example.com"),
     *               @OA\Property(property="firstName", type="string", default="", example="Hellen"),
     *               @OA\Property(property="lastName", type="string", default="", example="Keller"),
     *               @OA\Property(property="administratorName", type="string", default="", example="Hykon"),
     *               @OA\Property(property="mobile", type="string", default="", example="0123456789"),
     *               @OA\Property(property="website", type="string", default="", example="20325"),
     *               @OA\Property(property="role", type="string", default="", example="owner/propertyAdmin/company"),
     *               @OA\Property(property="landLine", type="string", default="", example="+1255487844"),
     *               @OA\Property(property="language", type="string", default="", example="en"),
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
     * @param UserPasswordHasherInterface $passwordHasher
     * @param ContainerUtility $containerUtility
     * @return View
     * @throws \Exception
     */
    public function create(Request $request, GeneralUtility $generalUtility, ValidationUtility $validationUtility,
                           RegistrationService $registrationService, ContainerUtility $containerUtility, UserPasswordHasherInterface $passwordHasher, UserService $userService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        if ($validationUtility->checkEmailAlreadyExists($request->request->get('email'))) {
            return $this->response($generalUtility->handleFailedResponse('userExists'));
        }
        $userIdentity = new UserIdentity();
        $form = $this->createNamedForm(PropertyAdminByOwnerRegistrationType::class, $userIdentity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->beginTransaction();
            try {
                $userIdentity->setLanguage($this->locale);
                $params = $registrationService->registerUser($form, $userIdentity, $passwordHasher, false);
                if ($form->has('sendInvite') && true === $form->get('sendInvite')->getData()) {
                    $containerUtility->sendEmailConfirmation($userIdentity, 'PropertyAdminInvitation',
                        $this->locale, 'PropertyAdminInvitation', $form->get('role')->getData(), $params, false, false, [], true);
                }
                $em->flush();
                $em->commit();
                $data = $generalUtility->handleSuccessResponse('userRegisteredSuccessfulWithoutMail',
                    $userService->getUserData($userIdentity));
                if ($request->request->get('sendInvite')) {
                    $data = $generalUtility->handleSuccessResponse('userRegisteredSuccessful',
                        $userService->getUserData($userIdentity));
                }
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
     * API end point to get company detail.
     *
     * # Request
     * In url, system expects company uuid.
     * # Response
     * ## Success response ##
     *       {
     *       "data": {
     *           "details": {
     *               "firstName": "Test",
     *               "lastName": "User",
     *               "email": "testbaluuser@yopmail.com",
     *               "isRegisteredUser": false,
     *               "invitedOn": "2022-05-04T13:07:31+00:00"
     *           },
     *           "allocations": [
     *               {
     *                   "roleName": "Tenant",
     *                   "roleKey": "tenant",
     *                   "objectName": "test",
     *                   "propertyName": "kk"
     *               },
     *               {
     *                   "roleName": "Tenant",
     *                   "roleKey": "tenant",
     *                  "objectName": "test",
     *                   "propertyName": "kk",
     *                   "startDate": "1995-12-22T00:00:00+00:00",
     *                   "endDate": "1995-12-22T00:00:00+00:00",
     *                   "nameEn": "open-end",
     *                   "nameDe": "offenes Ende",
     *                   "active": true
     *               },
     *               {
     *                   "roleName": "Tenant",
     *                   "roleKey": "tenant",
     *                   "objectName": "test",
     *                   "propertyName": "kk",
     *                   "startDate": "1995-12-22T00:00:00+00:00",
     *                   "endDate": "1995-12-22T00:00:00+00:00",
     *                   "active": true
     *               }
     *           ]
     *       },
     *       "error": false,
     *       "message": "dataFetchSuccess"
     *   }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "formError"
     *       }
     * @Route("/{uuid}/detail", name="balu_detail_admin", methods={"GET"})
     * @Operation(
     *      tags={"Administrator"},
     *      summary="API end point to get detail of an company",
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
     * @param string $uuid
     * @param GeneralUtility $generalUtility
     * @param CompanyService $companyService
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @return View
     */
    public function detail(string $uuid, GeneralUtility $generalUtility, CompanyService $companyService, LoggerInterface $requestLogger, Request $request): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $user = $em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $uuid, 'deleted' => false]);
        $data = $generalUtility->handleFailedResponse('dataFetchFailed');
        try {
            if (!$user instanceof UserIdentity) {
                throw new \Exception('invalidUser', 400);
            }
            $data = $generalUtility->handleSuccessResponse('dataFetchSuccess', $companyService->getAdministratorDetails($user, $this->getUser()));
        } catch (InvalidPasswordException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
        }

        return $this->response($data);
    }
}