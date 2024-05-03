<?php

/**
 * This file is part of the Balu Property package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Category;
use App\Entity\UserIdentity;
use App\Entity\UserPropertyPool;
use App\Form\CompanyRegistrationType;
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
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use App\Service\CompanyService;
use App\Entity\DamageOffer;
use App\Form\DamageOfferType;
use App\Service\DamageService;
use App\Entity\Damage;
use App\Entity\CompanyRating;
use App\Form\CompanyRatingType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use App\Entity\User;
use App\Form\CompanyUserRegistrationType;
use App\Entity\CompanySubscriptionPlan;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * CompanyController
 *
 * Controller to manage company related actions.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 * @Route("/company")
 */
final class CompanyController extends BaseController
{
    /**
     * API end point to category list of companies
     *
     *
     * # Request
     * No need to pass params to get the values
     *
     * ## Success response ##
     *
     *      {
     *      "data": [
     *          {
     *           "publicId": {
     *               "uid": "1ec7bba6-a831-63f8-878b-0242ac120003"
     *           },
     *           "name": "General electrical installations",
     *           "nameDe": "Allgemeine Elektroinstallationen"
     *          },
     *          {
     *           "publicId": {
     *               "uid": "1ec7bba6-a95c-600c-b9c3-0242ac120003"
     *           },
     *           "name": "Heating and ventilation installations",
     *           "nameDe": "Heizung-  und Lüftungsinstallationen"
     *          },
     *          {
     *           "publicId": {
     *               "uid": "1ec7bba6-aaff-6a8a-a089-0242ac120003"
     *           },
     *           "name": "Household appliances and repairs",
     *           "nameDe": "Haushaltgeräte und Reparaturen"
     *           },
     *          {
     *           "publicId": {
     *               "uid": "1ec7bba6-ad97-6ea0-8262-0242ac120003"
     *           },
     *           "name": "Interior and exterior doors",
     *           "nameDe": "Innen- und Aussentüren"
     *          }
     *      ],
     *      "error": false,
     *      "message": "listFetchSuccess"
     *      }
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns Company categoru lists",
     * ),
     * @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     * ),
     * @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     * ),
     * @OA\Tag(name="Company")
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @return View
     * @Route("/category-list", name="balu_company_category_list", methods={"GET"})
     */
    public function getCategories(GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request): View
    {
        $curDate = new \DateTime('now');
        try {
            $list = $this->doctrine->getRepository(Category::class)
                ->getCategories(['active' => true, 'deleted' => false], $this->locale);
            return $this->response($generalUtility->handleSuccessResponse('listFetchSuccess', $list));
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            return $this->response($generalUtility->handleFailedResponse($e->getMessage()));
        }
    }


    /**
     * API end point to company registration.
     *
     * # Request
     * In request body, system expects following data.
     * ### Route /api/2.0/company/create
     * ## Example request to update user settings
     *      {
     *          "email": "hellenkeller@example.com",
     *          "firstName": "Hellen",
     *          "lastName": "Keller",
     *          "companyName": "Company",
     *          "phone": "0123456789",
     *          "landLine": "0123456789",
     *          "website": "http://example.com",
     *          "role": "company",
     *          "language": 'en',
     *          "category": ["1ec7c784-4b9c-66ee-9248-0242ac120003"],
     *          "street": "Bern-Zürichstrasse",
     *          "streetNumber": "123",
     *          "city": "Langenthal",
     *          "zipCode": "123",
     *          "countryCode": "CH",
     *          "country": "Switzerland",
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
     * @Route("/create", name="balu_company_create", methods={"POST"})
     * @Operation(
     *      tags={"Company"},
     *      summary="API end point to administrator registration.",
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               @OA\Property(property="email", type="string", default="", example="hellenkeller@example.com"),
     *               @OA\Property(property="firstName", type="string", default="", example="Hellen"),
     *               @OA\Property(property="lastName", type="string", default="", example="Keller"),
     *               @OA\Property(property="administrationName", type="string", default="", example="Hykon"),
     *               @OA\Property(property="phone", type="string", default="", example="0123456789"),
     *               @OA\Property(property="website", type="string", default="", example="20325"),
     *               @OA\Property(property="role", type="string", default="", example="owner/propertyAdmin/company"),
     *               @OA\Property(property="landLine", type="string", default="", example="+1255487844"),
     *               @OA\Property(property="language", type="string", default="", example="en"),
     *               @OA\Property(
     *                      property="category",
     *                      type="array",
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003")
     *               ),
     *               @OA\Property(property="street", type="string", default="", example="Bern-Zürichstrasse"),
     *               @OA\Property(property="streetNumber", type="string", default="", example="123"),
     *               @OA\Property(property="city", type="string", default="", example="Langenthal"),
     *               @OA\Property(property="zipCode", type="string", default="", example="123"),
     *               @OA\Property(property="countryCode", type="string", default="", example="CH"),
     *               @OA\Property(property="country", type="string", default="", example="Switzerland"),
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
        $form = $this->createNamedForm(CompanyRegistrationType::class, $userIdentity, ['attr' => ['selfRegister' => false]]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->beginTransaction();
            try {
                $confirmationText = 'userRegisteredSuccessfulWithoutMail';
                $userIdentity->setLanguage($this->locale);
                $params = $registrationService->registerUser($form, $userIdentity, $passwordHasher, false);
                if ($form->has('sendInvite') && true === $form->get('sendInvite')->getData()) {
                    $userIdentity->setInvitedAt(new \DateTime("now"));
                    $containerUtility->sendEmailConfirmation($userIdentity, 'CompanyInvitation',
                        $this->locale, 'CompanyConfirmRegistration', $form->get('role')->getData(), $params);
                    $confirmationText = 'userRegisteredSuccessful';
                }
                $em->flush();
                $em->commit();
                $data = $generalUtility->handleSuccessResponse($confirmationText, $userService->getUserData($userIdentity));
            } catch (InvalidPasswordException | \Exception $e) {
                $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
                $em->rollback();
                $data = $generalUtility->handleFailedResponse($e->getMessage());
            }
        } else {
            $data = $generalUtility->handleFailedResponse('formError', 400, $this->getErrorsFromForm($form));
        }
        return $this->response($data);
    }

    /**
     * API end point to search companies
     *
     * # Request
     * Pass 'query' parameter to search
     *
     * ## Success response ##
     *
     *      {
     *           "data": [
     *               {
     *               "user": 54,
     *               "userPublicId": {
     *                   "uid": "1ece7e43-2c56-6dbc-81a8-0242ac120004"
     *               },
     *               "name": "vidya vidya",
     *               "isRegisteredUser": "false",
     *               "email": "vidya@test.com",
     *               "roleKey": "company",
     *               "enabled": true,
     *               "street": "sdfdsf",
     *               "streetNumber": "5",
     *               "city": "sdfsd",
     *               "country": "Algeria",
     *               "zipCode": "3242343",
     *               "isFavourite": "0",
     *               "category": [
     *                  {
     *                   "publicId": "1ecc5288-a850-600a-b4eb-0242ac120004",
     *                   "name": "sdf"
     *                  }
     *               ]
     *           }
     *       ],
     *       "error": false,
     *       "message": "Data fetched"
     *   }
     *
     * @OA\Parameter(
     *     name="query",
     *     in="query",
     *     required=false,
     *     @OA\Schema(
     *      @OA\Property(property="query", type="string", default="", example="hellenkeller@example.com")
     *     )
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Returns Company lists",
     * ),
     * @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     * ),
     * @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     * ),
     * @OA\Tag(name="Company")
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @return View
     * @Route("/search", name="balu_company_search", methods={"GET"})
     */
    public function searchCompany(Request $request, GeneralUtility $generalUtility, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        try {
            $list = $this->doctrine->getRepository(UserIdentity::class)->getCompanies($this->getUser(), $request->get('query'));
            $data = array_map(function ($item) {
                $em = $this->doctrine->getManager();
                $company = $em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $item['userPublicId']]);
                $locale = $this->locale;
                $locale = ($locale == 'de') ? ucfirst($locale) : '';
                foreach ($company->getCategories() as $key => $category) {
                    $item['category'][$key]['publicId'] = $category->getPublicId();
                    $item['category'][$key]['name'] = call_user_func_array([$category, 'getName' . $locale], []);
                }
                return $item;
            }, $list);
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse('formError', 400, $this->getErrorsFromForm($e->getMessage()));
        }

        return $this->response($generalUtility->handleSuccessResponse('listFetchSuccess', $data));
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
     * @Route("/{uuid}/detail", name="balu_detail_company", methods={"GET"})
     * @Operation(
     *      tags={"Company"},
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
        try {
            if (!$user instanceof UserIdentity) {
                throw new \Exception('invalidUser', 400);
            }
            $data = $generalUtility->handleSuccessResponse('dataFetchSuccess', $companyService->getCompanyDetail($user, $this->getUser()));
        } catch (InvalidPasswordException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to send offer details against a ticket
     *
     * # Request
     *
     *      {
     *          "ticket":"1ecdb203-8b0a-12tg4-asd5t-0242ac1b0004",
     *          "description":"desc10",
     *          "amount":"110",
     *          "offerField": [
     *              {
     *                "label": "lable 1 ",
     *                "amount": 12
     *              },
     *              {
     *                "label": "value2",
     *                "amount": 12
     *              }
     *          ],
     *          "attachment": [
     *               "1ecdb203-8ae0-652a-ad96-0242ac1b0004",
     *               "1ecdb203-8b0a-6e74-aecd-0242ac1b0004",
     *               "1ecdb203-8b38-609a-a363-0242ac1b0004"
     *           ],
     *          "currentStatus": "1ecdb203-8b62-6a84-979e-0242ac1b0004",
     *          "damageRequest": "3lcd2203-8b62-6a84-979e-0242ac1wd21e",
     *          "damageRequestStatus": "COMPANY_GIVE_OFFER_TO_OWNER",
     *          "statusUpdateRequired": true,
     *          "priceSplit": {
     *              "personal": 100,
     *              "material": 200
     *          }
     *       }
     *
     * ## Success response ##
     *
     *      {
     *       "data": {
     *           "publicId": {
     *               "uid": "1ecdb4c3-c5b4-6d0a-a715-0242ac1b0004"
     *           },
     *           "damage":"1ecdb203-8b0a-12tg4-asd5t-0242ac1b0004",
     *           "title": "title10",
     *           "description": "desc10",
     *           "amount":"110",
     *           "status": "open"
     *       },
     *       "error": false,
     *       "message": "damageOfferSuccess"
     *      }
     * @Operation(
     *      tags={"Company"},
     *      summary="API end point to add a ticket",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="ticket", type="string", default="", example=""),
     *               @OA\Property(property="description", type="string", default="", example=""),
     *               @OA\Property(property="amount", type="double", default="false", example=""),
     *               @OA\Property(
     *                      property="attachment",
     *                      type="array",
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003"),
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003")
     *               ),
     *               @OA\Property(property="currentStatus", type="string", default="", example="")
     *          )
     *       )
     *     ),
     *  @OA\Response(
     *     response=200,
     *     description="Returns success message after creating offfer",
     *  ),
     *  @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     *  ),
     *  @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     *  ),
     *  @OA\Tag(name="Company")
     * )
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param CompanyService $companyService
     * @param DamageService $damageService
     * @param LoggerInterface $requestLogger
     * @return View
     * @Route("/create-offer", name="balu_offer_create", methods={"POST"})
     */
    public function acceptDamageAndCreateOffer(Request $request, GeneralUtility $generalUtility, CompanyService $companyService, DamageService $damageService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $damageOffer = $request->get('offer') ? $em->getRepository(DamageOffer::class)
            ->findOneBy(['publicId' => $request->get('offer')]) : new DamageOffer();
        $form = $this->createNamedForm(DamageOfferType::class, $damageOffer);
        $form->handleRequest($request);
        $data = $generalUtility->handleFailedResponse('formError', 400, $this->getErrorsFromForm($form));
        if ($form->isSubmitted() && $form->isValid()) {
            $em->beginTransaction();
            try {
                $damage = $em->getRepository(Damage::class)->findOneBy(['publicId' => $request->get('ticket')]);
                $companyService->processOffer($request, $damage, $damageOffer, $this->getUser(), $this->currentRole);
                $damageService->persistOfferImages($damage, $this->getUser(), $damageOffer, $this->parameterBag->get('image_category')['offer_doc'], $request->get('attachment'));
                $statusUpdate = $request->get('statusUpdateRequired') && $request->get('statusUpdateRequired') == true;
                $damageService->updateStatus($request, $this->getUser(), $this->currentRole, $companyService->getStatus($damage), $statusUpdate);
                if (in_array($request->get('damageRequestStatus'), Constants::LOG_COMPANY_GIVE_OFFER) ||
                    in_array($request->get('damageRequestStatus'), Constants::LOG_COMPANY_GIVE_OFFER_TO_PRIVATE) ||
                    in_array($request->get('damageRequestStatus'), [Constants::COMPANY_ACCEPT_THE_DAMAGE])) {
                    $damageService->logDamage($this->getUser(), $damage, null, null, $damageOffer);
                }
                $responseStatus = 'damageOfferSuccess';
                if ($request->get('damageRequestStatus') == Constants::COMPANY_REJECT_THE_DAMAGE) {
                    $responseStatus = 'damageOfferRejectSuccess';
                } else if ($request->get('damageRequestStatus') == Constants::COMPANY_ACCEPT_THE_DAMAGE) {
                    $responseStatus = 'damageOfferAcceptSuccess';
                }
                $em->flush();
                $em->commit();
                $data = $generalUtility->handleSuccessResponse($responseStatus, $companyService->generateOfferDetails($damageOffer, $request));
            } catch (\Exception $e) {
                $em->rollBack();
                $data = $generalUtility->handleFailedResponse($e->getMessage());
                $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            }
        }

        return $this->response($data);
    }

    /**
     * API end point to get details of an offer
     *
     *
     * # Request
     *
     *
     * ## Success response ##
     *
     *           {
     *               "currentRole": "owner",
     *               "data": {
     *                   "offerNumber": "#80",
     *                   "publicId": "1ed13294-0d03-67be-bcd3-0242ac130003",
     *                   "damage": {
     *                     "publicId": "1ed0d7ff-acb8-6718-9bae-0242ac130003"
     *                    }
     *                   "createdOn": "2022-08-03T12:39:02+00:00",
     *                   "attachment": [
     *                            {
     *                                "publicId": "1ed13290-ab9c-6bcc-9776-0242ac130003",
     *                                "originalName": "any-165953025162ea6c0b346ca.png",
     *                                "path": "http://localhost:8001/files/property/folder-165416134862987fc407e88/folder-16541625406298846cd9fa5/damages/folder-165952822262ea641e1aa96/any-165953025162ea6c0b346ca.png",
     *                                "displayName": "any-165953025162ea6c0b346ca.png",
     *                                "filePath": "/var/www/app/balu/files/property/folder-165416134862987fc407e88/folder-16541625406298846cd9fa5/damages/folder-165952822262ea641e1aa96/any-165953025162ea6c0b346ca.png",
     *                                "isPrivate": "public",
     *                                "mimeType": "image/png",
     *                                "size": 95916.0,
     *                                "folder": "1ecf6cc0-00a5-677e-878d-0242ac130003",
     *                                "type": "offer_doc",
     *                                "encodedData": {
     *                                    "mimeType": "image/png",
     *                                    "document": "iVBOm9++VOGj52iJwYEtP7Dx8qa"
     *                                 }
     *                            }
     *                   ],
     *                   "customFields": [
     *                            {
     *                                "label": "lable 1 ",
     *                                "value": 12.0
     *                            },
     *                            {
     *                                "label": "lable 2 ",
     *                                "value": 12.0
     *                            }
     *                   ],    ,
     *                    "total": 110.0,
     *                    "accepted": true,
     *                    "company": {
     *                      "firstName": "Fn an",
     *                      "lastName": "Ln Eg",
     *                      "email": "test.company@yopmail.com"
     *                    }
     *               },
     *               "error": false,
     *               "message": "success"
     *           }
     *
     * @Operation(
     *      tags={"Company"},
     *      summary="API end point get details of an offer",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="offerId", type="string", default="", example="1ecdb203-7397-6490-b46f-0242ac1b0004"),
     *          )
     *       )
     *     ),
     *  @OA\Response(
     *     response=200,
     *     description="Returns offer details",
     *  ),
     *  @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     *  ),
     *  @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     *  ),
     *  @OA\Tag(name="Company")
     * )
     * @param Request $request
     * @param string $offerId
     * @param GeneralUtility $generalUtility
     * @param CompanyService $companyService
     * @param LoggerInterface $requestLogger
     * @return View
     * @Route("/offer/{offerId}", name="balu_offer_details", methods={"GET"})
     */
    public function details(Request $request, string $offerId, GeneralUtility $generalUtility, CompanyService $companyService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        try {
            $offer = $this->doctrine->getManager()->getRepository(DamageOffer::class)->findOneBy(['publicId' => $offerId]);
            $data = $generalUtility->handleSuccessResponse('success', $companyService->generateOfferDetails($offer, $request));
        } catch (ResourceNotFoundException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to rate a company
     *
     * ## Example request
     *      {
     *          "ticket":"1ec52818-da84-6248-96da-391aae371aa1",
     *          "rating": 5
     *      }
     *
     * # Response
     *
     * ## Success response ##
     *
     *       {
     *           "currentRole": "propertyAdmin",
     *           "data": {
     *                 "publicId": "1ed13c6d-37b6-66ae-8073-0242ac130003",
     *                 "createdOn": "2022-08-04T07:26:59+00:00",
     *                 "ratedBy": {
     *                             "firstName": "f name",
     *                             "lastName": "l name",
     *                            "email": "admin01@yopmail.com"
     *                         },
     *                 "company": {
     *                             "firstName": "Fn",
     *                             "lastName": "Ln",
     *                             "email": "test.company@yopmail.com"
     *                         },
     *                 "rating": 5
     *           },
     *           "error": false,
     *           "message": "ratingSuccess"
     *       }
     *
     * ## Failed response ##
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "ratingFailed."
     *      }
     * @Route("/rate", name="balu_rate_company", methods={"POST"})
     * @Operation(
     *      tags={"Company"},
     *      summary="API end point to rate a company",
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *             @OA\Property(property="ticket", type="string", default="", example=""),
     *              @OA\Property(property="rating", type="integer")
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
     * )
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param CompanyService $companyService
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function rateCompany(Request $request, GeneralUtility $generalUtility, CompanyService $companyService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $rating = new CompanyRating();
        $form = $this->createNamedForm(CompanyRatingType::class, $rating);
        $form->submit($request->request->all());
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->beginTransaction();
            try {
                $companyService->rateCompany($request, $rating, $this->getUser());
                $em->flush();
                $em->commit();
                $data = $generalUtility->handleSuccessResponse('ratingSuccess', $companyService->generateRatingDetails($rating->getDamage(), $request));
            } catch (\Exception $e) {
                $em->rollBack();
                $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
                $data = $generalUtility->handleFailedResponse($e->getMessage());
            }
        } else {
            $data = $generalUtility->handleFailedResponse('formError', 400, $this->getErrorsFromForm($form));
        }

        return $this->response($data);
    }

    /**
     * API end point to set reference number
     *
     * ## Example request
     *      {
     *          "ticket":"1ec52818-da84-6248-96da-391aae371aa1",
     *          "rating": 5
     *      }
     *
     * # Response
     *
     * ## Success response ##
     *
     *       {
     *           "currentRole": "company",
     *           "error": false,
     *           "message": "Internal Reference number added"
     *       }
     *
     * ## Failed response ##
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Adding Internal Reference number failed."
     *      }
     * @Route("/set-reference-number", name="balu_set_reference_number", methods={"PATCH"})
     * @Operation(
     *      tags={"Company"},
     *      summary="API end point to set reference number",
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *             @OA\Property(property="ticket", type="string", default="", example=""),
     *              @OA\Property(property="referenceNumber", type="string")
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
     * )
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param CompanyService $companyService
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function setReferenceNumber(Request $request, GeneralUtility $generalUtility, CompanyService $companyService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $data = $generalUtility->handleFailedResponse('badRequest');
        $em = $this->doctrine->getManager();
        $damage = $em->getRepository(Damage::class)->findOneBy(['publicId' => $request->get('ticket')]);
        if ($damage instanceof Damage) {
            if (!$companyService->validatePermission($this->getUser(), $damage)) {
                $data = $generalUtility->handleFailedResponse('defectNoPermissionToAddReference');
            } else {
                $em->beginTransaction();
                try {
                    $damage->setInternalReferenceNumber($request->get('referenceNumber'));
                    $data = $generalUtility->handleSuccessResponse('referenceNumberAdded');
                    $em->flush();
                    $em->commit();
                } catch (\Exception $e) {
                    $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
                    $em->rollBack();
                }
            }
        }

        return $this->response($data);
    }

    /**
     * API end point to add a company user.
     *
     * # Request
     * In request body, system expects details as JSON.
     * ## Example request to add individual
     *       {
     *           "email": "test16@yopmail.com",
     *           "firstName": "CH",
     *           "lastName": "Langenthal",
     *           "phone": "12.2221",
     *           "jobTitle": "Keller",
     *           "permission": ["VIEW_DAMAGE"],
     *       }
     * # Response
     * ## Success response ##
     *       {
     *           "data": {
     *               "firstName": "CH",
     *               "lastName": "Langenthal",
     *               "email": "test19@yopmail.com"
     *           },
     *           "error": false,
     *           "message": "Invitation successful"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "propertyCreateFail"
     *       }
     * @Route("/add/user", name="balu_add_company_user", methods={"POST"})
     * @Operation(
     *      tags={"Company"},
     *      summary="API end point to add a company user",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="email", type="string", default="", example=""),
     *               @OA\Property(property="firstName", type="string", default="", example=""),
     *               @OA\Property(property="lastName", type="string", default="", example=""),
     *               @OA\Property(property="phone", type="string", default="", example=""),
     *               @OA\Property(property="permission", type="string", default="", example=""),
     *               @OA\Property(property="jobTitle", type="string", default="", example="")
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
     * @param CompanyService $companyService
     * @param LoggerInterface $requestLogger
     * @param UserPasswordHasherInterface $passwordHasher
     * @return View
     */
    public function createCompanyUser(Request $request, GeneralUtility $generalUtility, UserService $userService, CompanyService $companyService, LoggerInterface $requestLogger, UserPasswordHasherInterface $passwordHasher): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            $user = $em->getRepository(User::class)->findOneBy(['property' => $request->get('email'), 'deleted' => false]);
            if ($user instanceof User) {
                throw new UnsupportedUserException('userExists');
            }
            $company = $this->getUser();
            $companySubscriptionPlan = $company->getCompanySubscriptionPlan();
            if (!$companySubscriptionPlan instanceof CompanySubscriptionPlan) {
                throw new InvalidArgumentException('noSubscriptionsFound');
            }
            $userIdentity = new UserIdentity();
            $form = $this->createNamedForm(CompanyUserRegistrationType::class, $userIdentity);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $companyService->saveCompanyUser($form, $userIdentity, $passwordHasher, $this->locale);
                $userIdentity->setParent($this->getUser());
                $em->flush();
                $data = $generalUtility->handleSuccessResponse('companyUserSuccessfull', $userService->getUserData($userIdentity));
                if ($companyService->checkSubscription($userIdentity, $this->getUser())) {
                    $data = $generalUtility->handleSuccessResponse('companyUserLimitReached', $userService->getUserData($userIdentity));
                }
            } else {
                $data = $generalUtility->handleFailedResponse('formError', 400, $this->getErrorsFromForm($form));
            }
            $em->flush();
            $em->commit();
        } catch (InvalidPasswordException | UnsupportedUserException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
            $em->rollBack();
        }

        return $this->response($data);
    }

    /**
     * API end point to edit a company user.
     *
     * # Request
     * In request body, system expects details as JSON.
     * ## Example request to add individual
     *       {
     *           "firstName": "CH",
     *           "lastName": "Langenthal",
     *           "phone": "12.2221",
     *           "jobTitle": "Keller",
     *           "permission": ["VIEW_DAMAGE"],
     *       }
     * # Response
     * ## Success response ##
     *       {
     *           "data": {
     *               "firstName": "CH",
     *               "lastName": "Langenthal",
     *               "email": "test19@yopmail.com"
     *           },
     *           "error": false,
     *           "message": "Invitation successful"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "fail"
     *       }
     * @Route("/user/{uuid}", name="balu_edit_company_user", methods={"PATCH"})
     * @Operation(
     *      tags={"Company"},
     *      summary="API end point to edit a company user",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="firstName", type="string", default="", example=""),
     *               @OA\Property(property="lastName", type="string", default="", example=""),
     *               @OA\Property(property="phone", type="string", default="", example=""),
     *               @OA\Property(property="permission", type="string", default="", example=""),
     *               @OA\Property(property="jobTitle", type="string", default="", example="")
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
     * @param string $uuid
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param UserService $userService
     * @param CompanyService $companyService
     * @param LoggerInterface $requestLogger
     * @param UserPasswordHasherInterface $passwordHasher
     * @return View
     */
    public function editCompanyUser(string $uuid, Request $request, GeneralUtility $generalUtility, UserService $userService,
                                    CompanyService $companyService, LoggerInterface $requestLogger, UserPasswordHasherInterface $passwordHasher): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        $data = $generalUtility->handleFailedResponse('invalidUser');
        try {
            $userIdentity = $em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $uuid, 'deleted' => false, 'parent' => $this->getUser()]);
            if ($userIdentity instanceof UserIdentity) {
                $form = $this->createNamedForm(CompanyUserRegistrationType::class, $userIdentity);
                $form->submit($request->request->all());
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $userService->updateUser($form, $userIdentity);
                    $companyService->addUserPermission($userIdentity, $form);
                    $em->flush();
                    $em->commit();
                    $data = $generalUtility->handleSuccessResponse('userUpdatedSuccessful', $userService->getUserData($userIdentity));
                } else {
                    return $this->response($generalUtility->handleFailedResponse('formError', 400, null, $this->getErrorsFromForm($form)));
                }
            }

        } catch (InvalidPasswordException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
            $em->rollBack();
        }

        return $this->response($data);
    }

    /**
     * API end point to get company users.
     *
     * # Response
     * ## Success response ##
     *   {
     *       "data": {
     *           "companyUsers": [
     *               {
     *                   "publicId": {
     *                       "uid": "1ed66529-d633-64d2-b944-0242ac120004"
     *                   },
     *                   "property": "umma@yopmail.com",
     *                   "firstName": "vidyaa",
     *                   "lastName": "sdfsdf",
     *                   "createdAt": "2022-11-17T08:34:13+00:00",
     *                   "companyName": "com",
     *                   "isExpired": false,
     *                   "userPermissions": "MANAGE_DAMAGE:MANAGE_DAMAGE"
     *               },
     *           ],
     *           "companyIsExpiring": false,
     *           "companyIsExpired": false,
     *           "count": "15"
     *       },
     *       "error": false,
     *       "message": "Company user created successfully"
     *   }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "fail"
     *       }
     * @Route("/user", name="balu_get_company_user", methods={"GET"})
     * @Operation(
     *      tags={"Company"},
     *      summary="API end point to get company users",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
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
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function listCompanyUsers(Request $request, GeneralUtility $generalUtility, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        try {
            $em = $this->doctrine->getManager();
            $param['offset'] = $request->get('offset');
            $param['limit'] = $request->get('limit');
            $users = $em->getRepository(UserIdentity::class)->getActiveCompanyUsers($this->getUser(), $param);
            $result['companyUsers'] = array_map(function ($users) {
                $users['isRegisteredUser'] = (bool)$users['isRegisteredUser'];
                return $users;
            }, $users);
            $result['totalUserCount'] = $em->getRepository(UserIdentity::class)->getActiveCompanyUsers($this->getUser(), [], true);
            $expiryDays = $this->parameterBag->get('company_expiry_days');
            $result['companyIsExpiring'] = $em->getRepository(UserIdentity::class)->checkIfCompanyIsExpiring($this->getUser(), $expiryDays);
            $result['companyIsExpired'] = $this->getUser()->getIsExpired();
            $result['maxUserAllowed'] = $this->getUser()->getCompanySubscriptionPlan()->getMaxPerson();
            $result['count'] = $em->getRepository(UserIdentity::class)->getActiveCompanyUsers($this->getUser(), $param, true);
            $data = $generalUtility->handleSuccessResponse('companyUserSuccessfull',
                $result);
        } catch (InvalidPasswordException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to edit a company user.
     *
     * # Request
     * In request body, system expects user uuid.
     * # Response
     * ## Success response ##
     *       {
     *           "data": [],
     *           "error": false,
     *           "message": "User delete successful"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "fail"
     *       }
     * @Route("/user/{uuid}", name="balu_delete_company_user", methods={"DELETE"})
     * @Operation(
     *      tags={"Company"},
     *      summary="API end point to delete a company user",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
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
     *
     * @param string $uuid
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function deleteCompanyUser(string $uuid, Request $request, GeneralUtility $generalUtility, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $data = $generalUtility->handleFailedResponse('invalidUser');
        $companyUser = $em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $uuid, 'deleted' => false]);
        $em->beginTransaction();
        try {
            if ($companyUser instanceof UserIdentity) {
                $property = $companyUser->getUser()->getProperty();
                $userMail = $property . '_deleted_' . date('d-m-Y h:i:s');
                $user = $companyUser->getUser();
                $user->setProperty($userMail)
                    ->setDeleted(true);
                $companyUser->setEnabled(false)
                    ->setDeleted(true);
                $propertyPool = $em->getRepository(UserPropertyPool::class)->findOneBy(['property' => $property]);
                $propertyPool->setProperty($userMail);
                $em->flush();
                $em->commit();
                $data = $generalUtility->handleSuccessResponse('userDeleted');
            }
        } catch (InvalidPasswordException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
            $em->rollBack();
        }

        return $this->response($data);
    }

    /**
     * API end point to fetch a company user detail.
     *
     * # Response
     * ## Success response ##
     *   {
     *       "data": {
     *           "publicId": {
     *               "uid": "1ed66626-a719-60f4-90f5-0242ac120004"
     *           },
     *           "property": "11@yopmail.com",
     *           "firstName": "df",
     *           "lastName": "sdfsdf",
     *           "createdAt": "2022-11-17T10:27:18+00:00",
     *           "phone": "1111111111111111",
     *           "jobTitle": "company",
     *           "userPermissions": [
     *               {
     *                   "key": "MANAGE_DAMAGE",
     *                   "value": "MANAGE_DAMAGE"
     *               },
     *               {
     *                   "key": "VIEW_DAMAGE",
     *                   "value": "VIEW_DAMAGE"
     *               }
     *           ]
     *       },
     *       "error": false,
     *       "message": "userDetail"
     *   }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "fail"
     *       }
     * @Route("/user/{uuid}", name="balu_detail_company_user", methods={"GET"})
     * @Operation(
     *      tags={"Company"},
     *      summary="API end point to fetch a company user detail",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
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
     *
     * @param string $uuid
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function companyUserDetail(string $uuid, Request $request, GeneralUtility $generalUtility, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $data = $generalUtility->handleFailedResponse('invalidUser');
        $companyUser = $em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $uuid, 'deleted' => false]);
        $em->beginTransaction();
        try {
            if ($companyUser instanceof UserIdentity) {
                $param['companyUser'] = $companyUser;
//                $param['companyUserRole'] = $this->parameterBag->get('user_roles')['company_user'];
                $result = $em->getRepository(UserIdentity::class)->getCompanyUserDetails($param);
                $data = $generalUtility->handleSuccessResponse('userDetail', $result);
            }
        } catch (InvalidPasswordException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
            $em->rollBack();
        }

        return $this->response($data);
    }

    /**
     * API end point to get company subscriptions.
     *
     * # Response
     * ## Success response ##
     *   {
     *       "data": {
     *
     *       },
     *       "error": false,
     *       "message": "Company subscriptions fetched successfully"
     *   }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "fail"
     *       }
     * @Route("/subscription", name="balu_get_company_subscriptions", methods={"GET"})
     * @Operation(
     *      tags={"Company"},
     *      summary="API end point to get company subscriptions",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
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
     * @param LoggerInterface $requestLogger
     * @param CompanyService $companyService
     * @return View
     */
    public function getCompanySubscriptionPlans(Request $request, GeneralUtility $generalUtility, LoggerInterface $requestLogger, CompanyService $companyService): View
    {
        $curDate = new \DateTime('now');
        $user = $this->getUser();
        $data = $generalUtility->handleFailedResponse('fail');
        try {
            if ($user instanceof UserIdentity) {
                $result = $companyService->getPlans($user, $this->locale);
                $data = $generalUtility->handleSuccessResponse('listFetchSuccess', $result);
            }
        } catch (InvalidPasswordException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to compare company plans
     *
     * # Request
     * # Response
     * ## Success response ##
     *       {
     *          "data": [
     *              {
     *                  "currentPlan": true,
     *                  "publicId": "1ed3ef57-947d-62d0-b054-5254a2026859",
     *                  "name": "FREE PLAN",
     *                  "period": 1,
     *                  "isFreePlan": true,
     *                  "currency": "CHF",
     *                  "details": []
     *              },
     *           ]
     *          "error": true,
     *          "message": "plans fetched successfully"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": [
     *              {
     *              },
     *           ]
     *          "error": true,
     *          "message": "plans fetched successfully"
     *       }
     * @Route("/compare", name="balu_compare_company_subscription", methods={"GET"})
     * @Operation(
     *      tags={"Company"},
     *      summary="API end point to compare plans.",
     *      @Security(name="Bearer"),
     *      @OA\Parameter(
     *      name="limit",
     *      in="query",
     *      description="Total number of results to be fetched",
     *      @OA\Schema(type="integer")
     *     ),
     *      @OA\Parameter(
     *      name="offset",
     *      in="query",
     *      description="Page",
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
     * @param CompanyService $companyService
     * @return View
     * @throws \Exception
     */
    public function compareSubscription(GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request,
                                        CompanyService $companyService): View
    {
        $curDate = new \DateTime('now');
        try {
            $plans = $companyService->comparePlans($this->getUser(), $request, $this->locale);
            $data = $generalUtility->handleSuccessResponse('planFetchSuccessfull', $plans);
        } catch (InvalidArgumentException | UnsupportedUserException | AccessDeniedException | Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to get company plan details
     *
     * # Request
     * # Response
     * ## Success response ##
     *       {
     *          "data": [
     *              {
     *                  "currentPlan": true,
     *                  "publicId": "1ed3ef57-947d-62d0-b054-5254a2026859",
     *                  "name": "FREE PLAN",
     *                  "period": 1,
     *                  "isFreePlan": true,
     *                  "currency": "CHF",
     *                  "details": []
     *              },
     *           ]
     *          "error": true,
     *          "message": "plans fetched successfully"
     *       }
     * ## Failed response ##
     * @Route("/plan/{planId}", name="balu_company_plan_details", methods={"GET"})
     * @Operation(
     *      tags={"Company"},
     *      summary="API end point to get plan details.",
     *      @Security(name="Bearer"),
     *      @OA\Parameter(
     *      name="limit",
     *      in="query",
     *      description="Total number of results to be fetched",
     *      @OA\Schema(type="integer")
     *     ),
     *      @OA\Parameter(
     *      name="offset",
     *      in="query",
     *      description="Page",
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
     * @param string $planId
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @param ParameterBagInterface $paramBag
     * @param CompanyService $companyService
     * @return View
     */
    public function getPlanDetails(string $planId, GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request, ParameterBagInterface $paramBag,
                                   CompanyService $companyService): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        try {
            $plan = $em->getRepository(CompanySubscriptionPlan::class)->findOneBy(['publicId' => $planId, 'deleted' => false]);
            if (!$plan instanceof CompanySubscriptionPlan) {
                throw new AccessDeniedException('invalidPlan');
            }
            $plans = $companyService->getPlanData($plan, $this->getUser(), $this->locale);
            $data = $generalUtility->handleSuccessResponse('planFetchSuccessful', $plans);
        } catch (InvalidArgumentException | UnsupportedUserException | AccessDeniedException | Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point fore more link
     *
     * # Request
     * # Response
     * ## Success response ##
     *       {
     *          "data": [
     *              {
     *                  "currentPlan": true,
     *                  "publicId": "1ed3ef57-947d-62d0-b054-5254a2026859",
     *                  "name": "FREE PLAN",
     *                  "period": 1,
     *                  "isFreePlan": true,
     *                  "currency": "CHF",
     *                  "details": []
     *              },
     *           ]
     *          "error": true,
     *          "message": "plans fetched successfully"
     *       }
     * ## Failed response ##
     * @Route("/plan/more/{planId}", name="balu_more_company_details", methods={"GET"})
     * @Operation(
     *      tags={"Company"},
     *      summary="API end point to get plan details.",
     *      @Security(name="Bearer"),
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
     * @param string $planId
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @param CompanyService $companyService
     * @return View
     */
    public function getMorePlanDetails(string $planId, GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request,
                                       CompanyService $companyService): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        try {
            $plan = $em->getRepository(CompanySubscriptionPlan::class)->findOneBy(['publicId' => $planId, 'deleted' => false]);
            if (!$plan instanceof CompanySubscriptionPlan) {
                throw new AccessDeniedException('invalidPlan');
            }
            $plans = $companyService->generateCompanyArray($plan);
            $data = $generalUtility->handleSuccessResponse('planFetchSuccessful', $plans);
        } catch (InvalidArgumentException | UnsupportedUserException | AccessDeniedException | Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to activate selected company users.
     * # Request
     * In request body, system expects object details as JSON.
     * ## Example request to edit an object
     *      {
     *           "users": ["1ecb66da-d42e-6784-bad1-0242ac120003", "1ec79eba-3ad0-6be6-b1b4-0242ac120004"]
     *      }
     * # Response
     * ## Success response ##
     *       {
     *           "data": [],
     *           "error": false,
     *           "message": "userActivationSuccess"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "propertyCreateFail"
     *       }
     * @Route("/activate/users", name="balu_activate_users", methods={"POST"})
     * @Operation(
     *      tags={"Company"},
     *      summary="API end point to activate company users.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *              @OA\Property(
     *                      property="objects",
     *                      type="array",
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003"),
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003")
     *               )
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
     * @param CompanyService $companyService
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function activateCompanyUsers(Request $request, CompanyService $companyService, GeneralUtility $generalUtility, LoggerInterface $requestLogger): view
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            if ($companyService->validateUser($this->getUser(), $request) == true) {
                $companyService->activateUsers($this->getUser(), $request);
                $em->flush();
                $users = $em->getRepository(UserIdentity::class)->getActiveCompanyUsers($this->getUser(), []);
                $em->getConnection()->commit();
                $data = $generalUtility->handleSuccessResponse('userActivationSuccess', $users);
            } else {
                $data = $generalUtility->handleFailedResponse('upgradeSubscription');
            }
        } catch (ResourceNotFoundException | \Exception $e) {
            $em->rollback();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to resend invitation.
     *
     * # Request
     * # Response
     * ## Success response ##
     *       {
     *       "data": {
     *       },
     *       "error": false,
     *       "message": "resendSuccess"
     *   }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "formError"
     *       }
     * @Route("/resend/{uuid}", name="balu_resend_company_invitation", methods={"POST"})
     * @Operation(
     *      tags={"Company"},
     *      summary="API end point to resend invitation",
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
     * @param UserPasswordHasherInterface $passwordHasher
     * @param Request $request
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function resendInvitation(string $uuid, GeneralUtility $generalUtility, CompanyService $companyService, UserPasswordHasherInterface $passwordHasher, Request $request, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        try {
            $user = $em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $uuid, 'deleted' => false]);
            if (!$user instanceof UserIdentity) {
                throw new \Exception('invalidUser', 400);
            }
            $user->setInvitedAt(new \DateTime("now"));
            $companyService->resendInvitation($user, $this->locale);
            $data = $generalUtility->handleSuccessResponse('resendInvitationSuccess');
            $em->flush();
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to get companies under selected categories.
     *
     * # Request
     * # Response
     * ## Success response ##
     *       {
     *       "data":[ {
     *              "publicId": "1ed4ace5-a5c8-6d5e-9fb6-00155d01d845",
     *              "firstName": "rewa",
     *              "lastName": "compant",
     *              "companyName": "busi",
     *              "property": "crewa@yopmail.com"
     *       }],
     *       "error": false,
     *       "message": "Data fetched"
     *   }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "formError"
     *       }
     * @Operation(
     *      tags={"Company"},
     *      summary="API end point to get companies under selected categories.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *      @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="category", type="string", default="", example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"),
     *           )
     *          )
     *       ),
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
     *
     * @param string $category
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @param CompanyService $companyService
     * @param string|null $damage
     * @return View
     * @Route("/category-company-list/{category}/{damage}", name="balu_category_based_company_list", methods={"GET"})
     */
    public function getCompaniesByCategory(string $category, GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request, CompanyService $companyService, ?string $damage = null): View
    {
        $curDate = new \DateTime('now');
        try {
            if (is_null($category)) {
                throw new InvalidArgumentException('invalidCategory');
            }
            $em = $this->doctrine->getManager();
            $iconDir = $generalUtility->getBaseUrl($request) . '/' . 'companies';
            $companies = $em->getRepository(Category::class)->getCompanies(['category' => $category, 'damage' => $damage, 'iconPath' => $iconDir]);
            return $this->response($generalUtility->handleSuccessResponse('listFetchSuccess', $companies));
        } catch (InvalidArgumentException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            return $this->response($generalUtility->handleFailedResponse($e->getMessage()));
        }
    }


    /**
     * API end point to get companies based on category and filter
     * # Request
     * In request body, system expects object details as JSON.
     * ## Example request to edit an object
     *      {
     *          "searchKey": "test",
     *          "damage": "1ee0059e-f132-6d18-b07d-00155d01d845"
     *      }
     * # Response
     * ## Success response ##
     *       {
     *       "data":[
     *                 {
     *                      "publicId": "1ed4ace5-a5c8-6d5e-9fb6-00155d01d845",
     *                      "firstName": "rewa",
     *                      "lastName": "compant",
     *                      "companyName": "busi",
     *                      "email": "crewa@yopmail.com",
     *                      "enabledCompany": true,
     *                      "isAlreadyAssigned": false,
     *                      "category": [
     *                          {
     *                              "catName": "Flooring / raised floors",
     *                              "catNameDE": "Bodenbeläge / Doppelböden",
     *                              "publicId": "1ec7c797-bbad-65a6-ae12-00155d01d845",
     *                              "icon": "http://localhost:8002/companies/floorpavement.png"
     *                          },
     *                          {
     *                              "catName": "Cheminee, pottery work",
     *                              "catNameDE": "Chéminée-, Hafnerarbeiten",
     *                              "publicId": "1ec7c797-bc43-6844-97dc-00155d01d845",
     *                              "icon": "http://localhost:8002/companies/fire.png"
     *                          }
     *                      ]
     *                  },
     *                  {
     *                      "publicId": "1ed4b0fd-6134-6dfc-aaf9-00155d01d845",
     *                      "firstName": "rewa",
     *                      "lastName": "com",
     *                      "companyName": "u",
     *                      "email": "c1rewa@yopmail.com",
     *                      "icon": "http://localhost:8002/companies/fire.png",
     *                      "enabledCompany": true,
     *                      "isAlreadyAssigned": true,
     *                      "category": {
     *                          "publicId": "1ec7c797-bc43-6844-97dc-00155d01d845",
     *                          "name": "Cheminee, pottery work"
     *                  }
     *              }
     *      ],
     *       "error": false,
     *       "message": "Data fetched"
     *   }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "formError"
     *       }
     * @Operation(
     *      tags={"Company"},
     *      summary="API end point to get companies based on category and filter.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               type="object",
     *               @OA\Property(
     *                  property="searchKey",
     *                  type="string",
     *                  default="test",
     *                  example="test"
     *              ),
     *               @OA\Property(
     *                  property="damage",
     *                  type="string",
     *                  default="1ee0059e-f132-6d18-b07d-00155d01d845",
     *                  example="1ee0059e-f132-6d18-b07d-00155d01d845"
     *              ),
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
     * )
     * @OA\Tag(name="Company")
     *
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param CompanyService $companyService
     * @Route("/category-company-list-filter", name="balu_category_based_company_list_filter", methods={"POST"})
     * @return View
     */
    public function getCompaniesByCategoryBasedOnFilter(Request $request, GeneralUtility $generalUtility, LoggerInterface $requestLogger, CompanyService $companyService): View
    {
        $curDate = new \DateTime('now');
        $requestArray = $request->request->all();
        try {
            $em = $this->doctrine->getManager();
            $requestArray['iconPath'] = $generalUtility->getBaseUrl($request) . '/' . 'companies';
            $companies = $em->getRepository(UserIdentity::class)->getCompaniesBasedOnFilter($requestArray);
            return $this->response($generalUtility->handleSuccessResponse('listFetchSuccess', $companies));
        } catch (InvalidArgumentException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            return $this->response($generalUtility->handleFailedResponse($e->getMessage()));
        }
    }

    /**
     * API end point to generate public sharable link for damages
     *
     * # Request
     * /api/2.0/company/generate-sharable/{damage}
     * # Response
     * ## Success response ##
     *      {
     *          "currentRole": "owner",
     *          "data": {
     *              "portalUrl": "https://portal.balu.property/#/public-ticket-details/1ee79add-4c3c-6a80-aba9-5254a2026859"
     *          },
     *          "error": false,
     *          "message": "Data fetched"
     *      }
     * ## Failed response ##
     * @Operation(
     *      tags={"Company"},
     *      summary="API end point to generate public sharable link for damages",
     *      @Security(name="Bearer"),
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
     * )
     *
     * @param string $damage
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param DamageService $damageService
     * @Route("/generate-sharable/{damage}", name="balu_generate_damage_public_link", methods={"GET"})
     * @return View
     */
    public function generatePublicLinkForDamage(string $damage, Request $request,
                                                GeneralUtility $generalUtility, LoggerInterface $requestLogger,
                                                DamageService $damageService): View
    {
        $curDate = new \DateTime('now');
        try {
            $data['portalUrl'] = $damageService->getPortalUrl($damage);
            return $this->response($generalUtility->handleSuccessResponse('listFetchSuccess', $data));
        } catch (InvalidArgumentException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            return $this->response($generalUtility->handleFailedResponse($e->getMessage()));
        }
    }
}