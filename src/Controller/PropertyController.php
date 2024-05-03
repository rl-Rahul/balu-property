<?php

/**
 * This file is part of the Balu Property package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Property;
use App\Entity\PropertyRoleInvitation;
use App\Form\PropertyRoleInvitationType;
use App\Service\UserService;
use App\Utils\Constants;
use App\Utils\GeneralUtility;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\View\View;
use App\Entity\UserIdentity;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use App\Form\PropertyType;
use App\Service\PropertyService;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use App\Entity\ObjectTypes;
use App\Entity\Role;
use App\Service\ObjectService;
use App\Entity\Apartment;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Entity\Folder;
use App\Entity\SubscriptionPlan;

/**
 * PropertyController
 *
 * Controller to manage property related actions.
 *
 * @package         BaluProperty
 * @subpackage      App
 * @author          pitsolutions.ch
 * @Route("/property")
 */
final class PropertyController extends BaseController
{
    /**
     * API end point to create new property.
     *
     * # Request
     * In request body, system expects property details as JSON.
     * ## Example request to create new property
     * ### Route /api/2.0/property/create
     * {
     *     "street": "Las vegas",
     *     "countryCode": "IN",
     *     "city": "Hykon",
     *     "latitude": "12.2221",
     *     "streetNumber": "Keller",
     *     "streetName": "Street name",
     *     "longitude": "11.11002",
     *     "country": "India",
     *     "address": "test address",
     *     "postalCode": "20154",
     *     "propertyGroup": "1ec79eba-3ad0-6be6-b1b4-0242ac120004",
     *     "owner": "1ec79eba-3ad0-6be6-b1b4-0242ac120004",
     *     "administrator": "1ec79eba-3ad0-6be6-b1b4-0242ac120004",
     *     "janitor": "1ec79eba-3ad0-6be6-b1b4-0242ac120004",
     *     "document": ["1ecb66da-d42e-6784-bad1-0242ac120003", "1ec79eba-3ad0-6be6-b1b4-0242ac120004"],
     *     "coverImage": ["1ec79eba-3ad0-6be6-b1b4-0242ac120004"]
     * }
     * # Response
     * ## Success response ##
     *       {
     *           "data": {
     *               "identifier": 25,
     *               "public_id": "1ec79eba-3ad0-6be6-b1b4-0242ac120004",
     *               "created_at": "2022-01-20T12:22:30+00:00",
     *               "deleted": false,
     *               "address": "hellenkeller@example.com",
     *               "street_name": "Hellen",
     *               "street_number": "Keller",
     *               "postal_code": "1995-12-22",
     *               "city": "Hykon",
     *               "country_code": "IN",
     *               "currency": "India",
     *               "plan_start_date": "2022-01-20T00:00:00+00:00",
     *               "plan_end_date": "2022-02-18T11:02:52+00:00",
     *               "active": true,
     *               "latitude": "1",
     *               "longitude": "owner/admin/tenant",
     *               "recurring": false,
     *               "pending_payment": false,
     *               "document": [],
     *               "subscription_plan": {
     *                   "identifier": 1,
     *                   "public_id": "00000000-0000-0000-0000-000000000000",
     *                   "created_at": "2022-01-19T11:11:53+00:00",
     *                   "updated_at": "2022-01-19T11:11:53+00:00",
     *                   "deleted": false,
     *                   "name": "free",
     *                   "period": 30,
     *                   "initial_plan": true,
     *                   "amount": 0.0,
     *                   "active": true,
     *                   "in_app_amount": 0.0,
     *                   "subscription_rates": []
     *               },
     *               "payment": [],
     *               "apartments": [],
     *               "favourite_companies": [],
     *               "folder_name": "p25"
     *           },
     *           "error": false,
     *           "message": "propertyCreateSuccess"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "propertyCreateFail"
     *       }
     * @Route("/create", name="balu_create_property", methods={"POST"})
     * @Operation(
     *      tags={"Property"},
     *      summary="API end point to create new property.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               @OA\Property(property="address", type="string", default="", example="test address"),
     *               @OA\Property(property="streetName", type="string", default="", example="Street name"),
     *               @OA\Property(property="streetNumber", type="string", default="", example="Street number"),
     *               @OA\Property(property="postalCode", type="string", default="", example="215222"),
     *               @OA\Property(property="city", type="string", default="", example="Hykon"),
     *               @OA\Property(property="street", type="string", default="", example="Las vegas"),
     *               @OA\Property(property="country", type="string", default="", example="India"),
     *               @OA\Property(property="countryCode", type="string", default="", example="IN"),
     *               @OA\Property(
     *                      property="document",
     *                      type="array",
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003"),
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003")
     *               ),
     *               @OA\Property(
     *                      property="coverImage",
     *                      type="array",
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003"),
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003")
     *               ),
     *               @OA\Property(property="latitude", type="boolean", default="", example="10.0555"),
     *               @OA\Property(property="longitude", type="string", default="", example="11.21251"),
     *               @OA\Property(property="owner", type="string", default="", example="1ec79eba-3ad0-6be6-b1b4-0242ac120004"),
     *               @OA\Property(property="propertyGroup", type="string", default="", example="1ec79eba-3ad0-6be6-b1b4-0242ac120004"),
     *               @OA\Property(property="administrator", type="string", default="", example="1ec79eba-3ad0-6be6-b1b4-0242ac120004"),
     *               @OA\Property(property="janitor", type="string", default="", example="1ec79eba-3ad0-6be6-b1b4-0242ac120004"),
     *           ),
     *       ),
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
     * @param PropertyService $propertyService
     * @param UserService $userService
     * @param LoggerInterface $requestLogger
     * @param ObjectService $objectService
     * @return View
     */
    public function create(Request $request, GeneralUtility $generalUtility, PropertyService $propertyService,
                           UserService $userService, LoggerInterface $requestLogger, ObjectService $objectService): View
    {
        $curDate = new \DateTime('now');
        $property = new Property();
        $form = $this->createNamedForm(PropertyType::class, $property);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->beginTransaction();
            try {
                $user = $this->getUser();
                $currentUserRole = $userService->getCurrentUserRole($user, $this->currentRole);
                $propertyOwner = $propertyService->findPropertyOwner($request, $currentUserRole, $user);
                $property = $propertyService->savePropertyInfo($request, $property, $user,
                    $propertyOwner, $this->currentRole, true, false);
                $em->refresh($property);
                $objectService->saveGeneralObjectInfo($property, $user, $request);
                $em->flush();
                $em->commit();
                if (!is_null($request->get('administrator')) || !is_null($request->get('janitor'))) {
                    $data = $generalUtility->handleSuccessResponse('propertySuccessInvite', ['id' => $property->getPublicId()]);
                } else {
                    $data = $generalUtility->handleSuccessResponse('propertySuccess', ['id' => $property->getPublicId()]);
                }
            } catch (InvalidArgumentException | UnsupportedUserException | AccessDeniedException | Exception $e) {
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
     * API end point to get a single property details
     *
     *
     * # Request
     * If ID is not passed, the details of logged in user will be returned.
     * ### Route /api/2.0/property/details/{propertyId}
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
     *            "profileImage": "uploads/ProfileImage/610b6a008ec44/screenshot4-610b6a008ef0a.png",
     *            "companyName": "company name",
     *            "gender": "Male",
     *            "roles": [
     *               "ROLE_USER"
     *             ],
     *            "deviceIds": [
     *                "45d0012c-3336-4d7a-b042-d46293b6b823"
     *             ],
     *            "latitude": "10.8012",
     *            "longitude": "10.2",
     *            "street": "street name",
     *            "zipCode": 65499,
     *            "houseNumber": "2344",
     *            "city": "Los Vegas", "education": [  "Engineer"  ],
     *            "industries": [  "Plumber",  "Electrician" ],
     *            "certificates": [ "https://wwww.0c8344769049dd4dfd851a5bb9f" ],
     *            "reference": [  "https://wwww.sdas9049dd4dfd851a5bb9f"  ]
     *            "rating": "4.5",
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
     * @OA\Tag(name="Property")
     * @param string $property
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param PropertyService $propertyService
     * @param LoggerInterface $requestLogger
     * @return View
     * @Route("/details/{property}", name="balu_get_single_property", methods={"GET"})
     */
    public function detail(string $property, Request $request, GeneralUtility $generalUtility, PropertyService $propertyService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        try {
            $property = $em->getRepository(Property::class)->findOneBy(array('publicId' => $property));
//            $this->denyAccessUnlessGranted('view', $property);
            if (!$property instanceof Property) {
                throw new EntityNotFoundException('invalidProperty');
            }
            $propertyArray = $propertyService->generatePropertyArray($property, $request, false, $this->locale, $this->getUser());
            $data = $generalUtility->handleSuccessResponse('propertyDetails', $propertyArray);
        } catch (Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to list the currently logged  in user's properties
     *
     *
     * # Request
     * If ID is not passed, the details of logged in user will be returned.
     * ### Route /api/2.0/property/list
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
     *            "profileImage": "uploads/ProfileImage/610b6a008ec44/screenshot4-610b6a008ef0a.png",
     *            "companyName": "company name",
     *            "gender": "Male",
     *            "roles": [
     *               "ROLE_USER"
     *             ],
     *            "deviceIds": [
     *                "45d0012c-3336-4d7a-b042-d46293b6b823"
     *             ],
     *            "latitude": "10.8012",
     *            "longitude": "10.2",
     *            "street": "street name",
     *            "zipCode": 65499,
     *            "houseNumber": "2344",
     *            "city": "Los Vegas", "education": [  "Engineer"  ],
     *            "industries": [  "Plumber",  "Electrician" ],
     *            "certificates": [ "https://wwww.0c8344769049dd4dfd851a5bb9f" ],
     *            "reference": [  "https://wwww.sdas9049dd4dfd851a5bb9f"  ]
     *            "rating": "4.5",
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
     *     summary="API end point to get get all properties.",
     *     @Security(name="Bearer"),
     *      @OA\Parameter(
     *      name="showdisabled",
     *      in="query",
     *      description="Whether to show all or active properties",
     *      @OA\Schema(type="int")
     *     )
     * )
     * @OA\Tag(name="Property")
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param PropertyService $propertyService
     * @param UserService $userService
     * @param string|null $group
     * @return View
     * @Route("/list", name="balu_list_property", methods={"GET"})
     */
    public function list(Request $request, GeneralUtility $generalUtility, PropertyService $propertyService, UserService $userService, ?string $group): View
    {
        $em = $this->doctrine->getManager();
        $user = $this->getUser();
        $data = $generalUtility->handleFailedResponse('propertyFetchFailed');
        if ($user instanceof UserIdentity) {
            $result['data'] = [];
            $params['limit'] = $request->get('limit') ? (int)$request->get('limit') : false;
            $params['page'] = $request->get('page');
            $params['offset'] = ($params['page'] != 0) ? ($params['page'] - 1) * $params['limit'] : $params['page'];
            // pagination change start
            if (!empty($params['page'])) {
                $params['offset'] = 0;
                $params['limit'] = $params['limit'] * $params['page'];
            } else {
                $params = [];
            }
            $params['showdisabled'] = $request->get('showdisabled', 1);
            $currentUserRole = $userService->getCurrentUserRole($user, $this->currentRole);
            $role = $em->getRepository(Role::class)->findOneBy(['roleKey' => $currentUserRole]);
            // pagination change end
            $results = $em->getRepository(Property::class)->getProperties($user, $params, $role);
            if (!empty($results)) {
                foreach ($results as $propertyResult) {
                    if (($params['showdisabled'] == 0) && $propertyService->checkPropertyCancelledOrExpired($propertyResult) != Constants::PROPERTY_ACTIVE)
                        continue;
                    $result['data'][] = $propertyService->generatePropertyArray($propertyResult, $request, false, $this->locale, $user);
                }
            }
            $result['count'] = $em->getRepository(Property::class)->countProperties($user, $role);
            if (isset($params['limit']) && $params['limit'] && $result['count']) {
                $result['maxPage'] = (int)ceil($result['count'] / $params['limit']);
            }
            $data = $generalUtility->handleSuccessResponse('propertyListSuccess', $result);
        }
        return $this->response($data);
    }

    /**
     * API end point to edit a property.
     *
     * # Request
     * In request body, system expects property details as JSON.
     * ## Example request to edit new property
     * ### Route /api/2.0/edit/{property}
     *   {
     *       "street": "Las vegas",
     *       "countryCode": "IN",
     *       "city": "Hykon",
     *       "latitude": true,
     *       "streetNumber": "Keller",
     *       "streetName": "Hellen",
     *       "ownerId": "owner/admin/tenant",
     *       "longitude": "owner/admin/tenant",
     *       "currency": "India",
     *       "address": "hellenkeller@example.com",
     *       "postalCode": "1995-12-22"
     *       "propertyGroup": "1ec79eba-3ad0-6be6-b1b4-0242ac120004",
     *   }
     * # Response
     * ## Success response ##
     *       {
     *           "data": {
     *               "identifier": 25,
     *               "public_id": "1ec79eba-3ad0-6be6-b1b4-0242ac120004",
     *               "created_at": "2022-01-20T12:22:30+00:00",
     *               "deleted": false,
     *               "address": "hellenkeller@example.com",
     *               "street_name": "Hellen",
     *               "street_number": "Keller",
     *               "postal_code": "1995-12-22",
     *               "city": "Hykon",
     *               "country_code": "IN",
     *               "currency": "India",
     *               "plan_start_date": "2022-01-20T00:00:00+00:00",
     *               "plan_end_date": "2022-02-18T11:02:52+00:00",
     *               "active": true,
     *               "latitude": "1",
     *               "longitude": "owner/admin/tenant",
     *               "recurring": false,
     *               "pending_payment": false,
     *               "document": [],
     *               "subscription_plan": {
     *                   "identifier": 1,
     *                   "public_id": "00000000-0000-0000-0000-000000000000",
     *                   "created_at": "2022-01-19T11:11:53+00:00",
     *                   "updated_at": "2022-01-19T11:11:53+00:00",
     *                   "deleted": false,
     *                   "name": "free",
     *                   "period": 30,
     *                   "initial_plan": true,
     *                   "amount": 0.0,
     *                   "active": true,
     *                   "in_app_amount": 0.0,
     *                   "subscription_rates": []
     *               },
     *               "payment": [],
     *               "apartments": [],
     *               "favourite_companies": [],
     *               "folder_name": "p25"
     *           },
     *           "error": false,
     *           "message": "propertyCreateSuccess"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "propertyCreateFail"
     *       }
     * @Route("/edit/{property}", name="balu_edit_property", methods={"POST"})
     * @Operation(
     *      tags={"Property"},
     *      summary="API end point to edit a property.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="address", type="string", default="", example="hellenkeller@example.com"),
     *               @OA\Property(property="streetName", type="string", default="", example="Hellen"),
     *               @OA\Property(property="streetNumber", type="string", default="", example="Keller"),
     *               @OA\Property(property="postalCode", type="string", default="", example="1995-12-22"),
     *               @OA\Property(property="city", type="string", default="", example="Hykon"),
     *               @OA\Property(property="street", type="string", default="", example="Las vegas"),
     *               @OA\Property(property="currency", type="string", default="", example="India"),
     *               @OA\Property(property="countryCode", type="string", default="", example="IN"),
     *               @OA\Property(property="document", type="string", default="", example="20325"),
     *               @OA\Property(property="latitude", type="boolean", default="", example="true"),
     *               @OA\Property(property="longitude", type="string", default="", example="owner/admin/tenant"),
     *               @OA\Property(property="ownerId", type="string", default="", example="owner/admin/tenant")
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
     * @param string $property
     * @param GeneralUtility $generalUtility
     * @param PropertyService $propertyService
     * @param UserService $userService
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function edit(Request $request, string $property, GeneralUtility $generalUtility, PropertyService $propertyService, UserService $userService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        if (!$property = $em->getRepository(Property::class)->findOneBy(['publicId' => $property, 'deleted' => false])) {
            throw new AccessDeniedException('notValidProperty');
        }
        $form = $this->createNamedForm(PropertyType::class, $property);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->beginTransaction();
            try {
                $user = $this->getUser();
                $currentUserRole = $userService->getCurrentUserRole($user, $this->currentRole);
                $propertyOwner = $propertyService->findPropertyOwner($request, $currentUserRole, $user);
                $property = $propertyService->savePropertyInfo($request, $property, $user, $propertyOwner, $this->currentRole, true, true);
                $em->flush();
                $em->commit();
                if (($property->getJanitor() instanceof UserIdentity && $property->getJanitor()->getPublicId() != $request->get('janitor'))
                    || ($property->getAdministrator() instanceof UserIdentity && $property->getAdministrator()->getPublicId() != $request->get('administrator'))
                    || ($property->getJanitor() == null && !empty($request->get('janitor')))
                    || ($property->getAdministrator() == null && !empty($request->get('administrator')))) {
                    $data = $generalUtility->handleSuccessResponse('propertyEditedInvitedSuccessfully', $propertyService->generatePropertyArray($property, $request, true, $this->locale, $user));
                } else {
                    $data = $generalUtility->handleSuccessResponse('propertyEditedSuccessfully', $propertyService->generatePropertyArray($property, $request, true, $this->locale, $user));
                }
            } catch (InvalidArgumentException | UnsupportedUserException | AccessDeniedException | Exception $e) {
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
     * API end point to filter and list the currently logged  in user's properties
     *
     * ## Pass 0 or 1 => activeContract (pass nothing for 'any' option)
     * ## Pass "rental" or "ownership" => contractType, (pass nothing for 'any' option)
     * ##objectType should be array
     * # Request
     *
     * ##Example Request
     *      {
     *          "objectType": [
     *              "1ecd2886-5eae-6d8c-b623-0242ac120004"
     *          ],
     *          "activeContract":0,
     *          "contractType": "ownership"
     *      }
     * If ID is not passed, the details of logged in user will be returned.
     * ### Route /api/2.0/property/filter
     * ## Success response ##
     *{
     *   "data": {
     *       "data": [],
     *       "count": 31,
     *       "maxPage": 4
     *   },
     *   "error": false,
     *   "message": "propertyListSuccess"
     * }
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns filtered property list",
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
     *     summary="API end point to filter property list.",
     *     @Security(name="Bearer")
     * )
     * @OA\Tag(name="Property")
     * @param string $property
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param PropertyService $propertyService
     * @param LoggerInterface $requestLogger
     * @return View
     * @Route("/{property}/filter", name="balu_filter_property", methods={"POST"})
     */
    public function filter(string $property, Request $request, GeneralUtility $generalUtility, PropertyService $propertyService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $user = $this->getUser();
        if (!$property = $em->getRepository(Property::class)->findOneBy(['publicId' => $property, 'deleted' => false])) {
            return $this->response($generalUtility->handleFailedResponse('invalidProperty'));
        }
        try {
            $objectType = $request->get('objectType') ?? [];
            $params['objectType'] = array_map(function ($objects) use ($em) {
                return $em->getRepository(ObjectTypes::class)->findOneBy(['publicId' => $objects]);
            }, $objectType);
            $params['activeContract'] = $request->get('activeContract');
            $params['contractType'] = $request->get('contractType');
            $result = $propertyService->getFilteredList($property, $user, $params, $this->locale, $request, $request->get('count'), $request->get('page'));
            $data = $generalUtility->handleSuccessResponse('propertyListSuccess', $result);
        } catch (Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse('failed');
        }

        return $this->response($data);
    }

    /**
     * API end point to delete properties of logged in user (parameters: property(comma separated property ids)) ",
     *
     * # Request
     * # Response
     * ## Success response ##
     *       {
     *           "error": false,
     *           "message": "Property deleted successfully"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Property not found"
     *       }
     * @Route("/delete/{property}", name="balu_delete_property", methods={"DELETE"})
     * @Operation(
     *      tags={"Property"},
     *      summary="API end point to delete a property.",
     *      @Security(name="Bearer"),
     *      @OA\Parameter(
     *      name="query",
     *      in="query",
     *      required=false,
     *      @OA\Schema(
     *          @OA\Property(property="property", type="string", default="", example="1ecdb203-7397-6490-b46f-0242ac1b0004,1ecdb203-7397-6490-b46f-0242ac1b0002")
     *       )
     *      ),
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
     * @param string $property
     * @param Request $request
     * @param ObjectService $objectService
     * @param GeneralUtility $generalUtility
     * @param UserService $userService
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function delete(string $property, Request $request, ObjectService $objectService, GeneralUtility $generalUtility, UserService $userService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            $user = $this->getUser();
//            $properties = array_filter(explode(',', $request->get('property')));
//            foreach ($properties as $property) {
            $property = $em->getRepository(Property::class)->findOneBy(['publicId' => $property, 'deleted' => 0]);
            if (!$property instanceof Property || $property->getUser()->getId() != $user->getId() && $userService->isPropertyAdmin($property) === false) {
                throw new AccessDeniedException('invalidProperty');
            }
            $apartments = $em->getRepository(Apartment::class)->findBy([
                'property' => $property->getId(),
                'active' => 1]);
            foreach ($apartments as $apartment) {
                $objectService->deleteObject($apartment, $this->locale);
            }
            $em->getRepository(Folder::class)->deleteChildFolders($property->getFolder());
            $property->getFolder()->setDeleted(1);
            $property->setDeleted(1);
            $property->setActive(0);
            $em->flush();
//            }
            $em->commit();
            $data = $generalUtility->handleSuccessResponse('propertyDeleteSuccess');
        } catch (ResourceNotFoundException | Exception $e) {
            $em->rollback();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to get expiring properties list
     *
     * # Request
     * # Response
     * ## Success response ##
     *       {
     *           "error": false,
     *           "message": "Property listed successfully"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Property not found"
     *       }
     * @Route("/expiring/list", name="balu_expiring_list", methods={"GET"})
     * @Operation(
     *      tags={"Property"},
     *      summary="API end point to get expiring properties list.",
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
     * @param ParameterBagInterface $paramBag
     * @param PropertyService $propertyService
     * @return View
     */
    public function expiringList(GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request, ParameterBagInterface $paramBag,
                                 PropertyService $propertyService): View
    {
        $curDate = new \DateTime('now');
        try {
            $em = $this->doctrine->getManager();
            $user = $this->getUser();
            $expirationLimit = $paramBag->get('expiration_limit');
            $result = $active = $finalData = $freePlan = [];
            if ($user instanceof UserIdentity && in_array($this->currentRole, [$this->parameterBag->get('user_roles')['owner'], $this->parameterBag->get('user_roles')['property_admin']])) {
                $param['offset'] = $request->get('offset');
                $param['limit'] = $request->get('limit');
                $results = $em->getRepository(Property::class)->getExpiringProperties($user, $expirationLimit, $param);
                foreach ($results as $propertyResult) {
                    $propertyArray = $propertyService->generateSubscriptionArray($propertyResult, $request);
                    $result[] = $propertyArray;
                }
                $finalData['expiring'] = $result;
                $finalData['expiringCount'] = $em->getRepository(Property::class)->getExpiringProperties($user, $expirationLimit, null, true);

                $activeProperties = $em->getRepository(Property::class)->getActiveProperties($user, $param);
                foreach ($activeProperties as $activePropertyResult) {
                    $propertyArray = $propertyService->generateSubscriptionArray($activePropertyResult, $request);
                    $active[] = $propertyArray;
                }
                $finalData['active'] = $active;

                $freePlanProperties = $em->getRepository(Property::class)->getInitialPlanProperties($user, $expirationLimit);
                foreach ($freePlanProperties as $freePlanPropertyResult) {
                    $propertyArray = $propertyService->generateSubscriptionArray($freePlanPropertyResult, $request);
                    $freePlan[] = $propertyArray;
                }

                $finalData['freePlan'] = $freePlan;
            }
            $data = $generalUtility->handleSuccessResponse('listFetchSuccess', $finalData);
        } catch (ResourceNotFoundException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to update property expiry date
     *
     * # Request
     * # Response
     * ## Success response ##
     *       {
     *           "error": false,
     *           "message": "Property exipiry updated successfully"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Property not found"
     *       }
     * @Route("/change-expiry-date", name="balu_expiring_change", methods={"POST"})
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
     * @param PropertyService $propertyService
     * @param UserService $userService
     * @return View
     */
    public function changeExpiryDate(GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request,
                                     PropertyService $propertyService, UserService $userService): View
    {
        $em = $this->doctrine->getManager();
        $curTime = new \DateTime('now');
        $em->beginTransaction();
        try {
            $endDateWithTime = 'now';
            if (!empty($request->get('planEndDate'))) {
                $endDateWithTime = $request->get('planEndDate') . ' ' . $curTime->format('H:i:s');
            }
            $planEndDate = new \DateTime($endDateWithTime);
            $currentUserRole = $userService->getCurrentUserRole($this->getUser(), $this->currentRole);
            if ($currentUserRole !== $this->parameterBag->get('user_roles')['admin']) {
                return $this->response($generalUtility->handleFailedResponse('unauthorizedAccess'));
            }
            $property = $em->getRepository(Property::class)->findOneBy(['publicId' => $request->get('propertyId'), 'deleted' => false]);
            $existingEndDate = clone $property->getPlanEndDate();
            $property->setPlanEndDate($planEndDate);
            $expiryDateDiff = $curTime->diff($planEndDate)->format('%r%a');
            if ($expiryDateDiff >= 0 && $property->getActive() === false) {
                $property->setActive(true);
            } elseif ($expiryDateDiff < 0 && $property->getActive() === TRUE) {
                $property->setActive(false);
            }
            if ($property->getRecurring() === true) {
                $cancelSubscription = $propertyService->cancelSubscription($request);
                if ($cancelSubscription) {
                    $data = $generalUtility->handleSuccessResponse('unsubscribeSuccess');
                } else {
                    $property->setPlanEndDate($existingEndDate);
                    $em->flush();
                    $data = $generalUtility->handleFailedResponse('fail');
                }
            }
            $property->setUpdatedAt($curTime);
            $em->flush();
            $em->commit();
            $result = $propertyService->generatePropertyArray($property, $request, true, $this->locale, $this->getUser());
            $data = $generalUtility->handleSuccessResponse('expiryDateUpdatedSuccessfully', $result);
        } catch (ResourceNotFoundException | \Exception $e) {
            $em->rollback();
            $requestLogger->error($curTime->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point get list of properties with subscription details
     *
     * # Request
     * # Response
     * ## Success response ##
     *      {
     *          "publicId": "1ed3f025-8fac-6fb6-96c3-5254a2026859",
     *          "id": 3,
     *          "address": "My home",
     *           "streetName": "Technopark Phase l",
     *           "active": true,
     *           "streetNumber": "695581",
     *           "postalCode": "123",
     *           "planEndDate": "2023-10-28T08:24:16+00:00",
     *           "planStartDate": "2022-09-28T00:00:00+00:00",
     *           "activeObjectCount": 1,
     *           "totalObjectCount": 1,
     *           "subscriptionPlan": {
     *                   "publicId": "1ed3ef57-9486-6bdc-8791-5254a2026859",
     *                   "apartmentMin": 1,
     *                   "apartmentMax": 2,
     *                   "name": "1 - 2 Apartments 30  Days",
     *                   "amount": 10.0,
     *                   "period": "Yearly Payment",
     *                   "isFreePlan": false,
     *                   "details": [],
     *                   "currency": "CHF"
     *           },
     *           "isSubscriptionCancelled": false,
     *           "coverImage": [
     *                   {
     *                       "identifier": 3,
     *                       "publicId": "1ed3f025-e72e-6b90-858c-5254a2026859",
     *                       "originalName": "reneporterhtegzefub7wunsplash-16643514266333fcc2800a9.jpg",
     *                       "path": "http://localhost:8001/files/property/folder-16643514836333fcfb78711/coverImage/reneporterhtegzefub7wunsplash-16643514266333fcc2800a9.jpg",
     *                       "displayName": "rene-porter-hteGzeFuB7w-unsplash",
     *                       "type": "coverImage",
     *                       "filePath": "/usr/www/users/balufj/api-balu2-stage/files/property/folder-16643514836333fcfb78711/coverImage/reneporterhtegzefub7wunsplash-16643514266333fcc2800a9.jpg",
     *                       "isPrivate": "public",
     *                       "mimeType": "image/jpeg",
     *                       "size": 1370478.0,
     *                       "folder": "1ed3f025-90e4-6118-98dc-5254a2026859",
     *                       "thumbnails": {
     *                               "image_345X180": "http://localhost:8001/files/property/folder-16643514836333fcfb78711/coverImage/345-180-reneporterhtegzefub7wunsplash-16643514266333fcc2800a9.jpg",
     *                               "image_50X50": "http://localhost:8001/files/property/folder-16643514836333fcfb78711/coverImage/50-50-reneporterhtegzefub7wunsplash-16643514266333fcc2800a9.jpg",
     *                               "image_40X40": "http://localhost:8001/files/property/folder-16643514836333fcfb78711/coverImage/40-40-reneporterhtegzefub7wunsplash-16643514266333fcc2800a9.jpg",
     *                               "image_130X130": "http://localhost:8001/files/property/folder-16643514836333fcfb78711/coverImage/130-130-reneporterhtegzefub7wunsplash-16643514266333fcc2800a9.jpg",
     *                               "image_90X90": "http://localhost:8001/files/property/folder-16643514836333fcfb78711/coverImage/90-90-reneporterhtegzefub7wunsplash-16643514266333fcc2800a9.jpg",
     *                               "image_544X450": "http://localhost:8001/files/property/folder-16643514836333fcfb78711/coverImage/544-450-reneporterhtegzefub7wunsplash-16643514266333fcc2800a9.jpg"
     *                       }
     *                   }
     *           ],
     *           "currency": "CHF"
     *      }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Property not found"
     *       }
     * @Route("/list/subscriptions", name="balu_list_subscription_properties", methods={"GET"})
     * @Operation(
     *      tags={"Property"},
     *      summary="API end point get list of properties with subscription details",
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
     * @param PropertyService $propertyService
     * @param ParameterBagInterface $parameterBag
     * @return View
     */
    public function listPropertiesWithSubscription(GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request,
                                                   PropertyService $propertyService, ParameterBagInterface $parameterBag): View
    {
        $curDate = new \DateTime('now');
        try {
            $em = $this->doctrine->getManager();
            $user = $this->getUser();
            $expirationLimit = $parameterBag->get('expiration_limit');
            $result = $active = $finalData = $freePlan = [];
            if ($user instanceof UserIdentity && in_array($this->currentRole, [$parameterBag->get('user_roles')['owner'], $parameterBag->get('user_roles')['property_admin']])) {
                $param['offset'] = $request->get('offset');
                $param['limit'] = $request->get('limit');
                $results = $em->getRepository(Property::class)->getPropertiesWithSubscriptions($user, $expirationLimit, $param);
                foreach ($results as $propertyResult) {
                    $result[] = $propertyService->setPropertyArray($propertyResult, $request, $this->locale);
                }
            }
            $data = $generalUtility->handleSuccessResponse('listFetchSuccess', $result);
        } catch (ResourceNotFoundException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to get plan list
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
     * @Route("/compare/{property}", name="balu_compare_subscription", methods={"GET"})
     * @Operation(
     *      tags={"Property"},
     *      summary="API end point to get plans list.",
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
     * @param string $property
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @param ParameterBagInterface $paramBag
     * @param PropertyService $propertyService
     * @return View
     */
    public function compareSubscription(string $property, GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request, ParameterBagInterface $paramBag,
                                        PropertyService $propertyService): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        if (!$property = $em->getRepository(Property::class)->findOneBy(['publicId' => $property, 'deleted' => false])) {
            throw new AccessDeniedException('notValidProperty');
        }
        try {
            $plans = $propertyService->comparePlans($property, $request, $this->locale);
            $data = $generalUtility->handleSuccessResponse('planFetchSuccessfull', $plans);
        } catch (InvalidArgumentException | UnsupportedUserException | AccessDeniedException | Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to get plan details
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
     * @Route("/{property}/plan/{planId}", name="balu_pan_details", methods={"GET"})
     * @Operation(
     *      tags={"Property"},
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
     * @param string $property
     * @param string $planId
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @param ParameterBagInterface $paramBag
     * @param PropertyService $propertyService
     * @return View
     */
    public function getPlanDetails(string $property, string $planId, GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request, ParameterBagInterface $paramBag,
                                   PropertyService $propertyService): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        try {
            $property = $em->getRepository(Property::class)->findOneBy(['publicId' => $property, 'deleted' => false]);
            if (!$property instanceof Property) {
                throw new AccessDeniedException('notValidProperty');
            }
            $plan = $em->getRepository(SubscriptionPlan::class)->findOneBy(['publicId' => $planId, 'deleted' => false]);
            if (!$plan instanceof SubscriptionPlan) {
                throw new AccessDeniedException('invalidPlan');
            }
            $plans = $propertyService->getPlanData($plan, $property, $this->locale);
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
     * @Route("/plan/more/{planId}", name="balu_more_details", methods={"GET"})
     * @Operation(
     *      tags={"Property"},
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
     * @param PropertyService $propertyService
     * @return View
     */
    public function getMorePlanDetails(string $planId, GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request,
                                       PropertyService $propertyService): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        try {
            $plan = $em->getRepository(SubscriptionPlan::class)->findOneBy(['publicId' => $planId, 'deleted' => false]);
            if (!$plan instanceof SubscriptionPlan) {
                throw new AccessDeniedException('invalidPlan');
            }
            $plans = $propertyService->getPlanDetails($plan, $this->locale);
            $data = $generalUtility->handleSuccessResponse('planFetchSuccessful', $plans);
        } catch (InvalidArgumentException | UnsupportedUserException | AccessDeniedException | Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to set property administrator invite response.
     *
     * # Request
     * In request body, system expects administrator response and reason.
     * ## Example request to set new user password
     *      {
     *          "accepted": "true"
     *          "reason": "test test"
     *          "role": "janitor"
     *          "property": "23eds-wZUFVcW1-bg532-di9NNG5rb-U4vWm5FWj"
     *      }
     * # Response
     * ## Success response ##
     *      {
     *          "error": false,
     *          "data": "No data provided",
     *          "message": "Property Administration set successfully"
     *      }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *        "data": "No data provided",
     *        "error": true,
     *        "message": "Administration setting failed"
     *      }
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
     *     summary="API end point to set administrator response.",
     *     @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               type="object",
     *               @OA\Property(
     *                  property="accepted",
     *                  type="boolean",
     *                  default="true",
     *                  example="true"
     *              ),
     *              @OA\Property(
     *                  property="reason",
     *                  type="string",
     *                  default="",
     *                  example="test string"
     *              ),
     *              @OA\Property(
     *                  property="role",
     *                  type="string",
     *                  default="",
     *                  example="janitor/property_admin"
     *              ),
     *              @OA\Property(
     *                  property="property",
     *                  type="string",
     *                  default="",
     *                  example="23eds-wZUFVcW1-bg532-di9NNG5rb-U4vWm5FWj"
     *              )
     *           )
     *       )
     *     ),
     * )
     * @OA\Tag(name="Property")
     * @Route("/administration-confirmation", name="balu_administration_confirmation", methods={"POST"})
     *
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param PropertyService $propertyService
     * @return View
     */
    public function administrationConfirmation(Request $request, GeneralUtility $generalUtility,
                                               LoggerInterface $requestLogger, PropertyService $propertyService): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $acceptFlag = $request->get('accepted');
        $property = $em->getRepository(Property::class)->findOneBy(['publicId' => $request->get('property')]);
        $role = $em->getRepository(Role::class)->findOneBy(['roleKey' => $request->get('role')]);
        $propertyRoleInvitation = $em->getRepository(PropertyRoleInvitation::class)->findOneBy(
            ['property' => $property, 'deleted' => false, 'role' => $role, 'invitationAcceptedDate' => null]);
        $form = $this->createNamedForm(PropertyRoleInvitationType::class, $propertyRoleInvitation);
        $form->submit($request->request->all());
        $data = $generalUtility->handleFailedResponse('formError', 400, $this->getErrorsFromForm($form));
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->beginTransaction();
            $roleBasedResponse = ($role->getRoleKey() == Constants::JANITOR_ROLE) ? 'propertyJanitorSuccess' : 'propertyAdministrationSuccess';
            $responseMsg = ($acceptFlag == true) ? $roleBasedResponse : 'propertyAdministrationRejectSuccess';
            try {
                $data = $generalUtility->handleSuccessResponse(
                    $responseMsg,
                    $propertyService->savePropertyRoleInvitation($request->request->all(), $propertyRoleInvitation, $property)
                );
                $em->commit();
            } catch (ResourceNotFoundException | \Exception $e) {
                $em->rollBack();
                $data = $generalUtility->handleFailedResponse($e->getMessage());
                $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            }
        }

        return $this->response($data);
    }

    /**
     * API end point to get property details of given id
     *
     * # Request
     * ### Route /api/2.0/property/user-details
     * ## Success response ##
     *
     *      {
     *          "error": false,
     *          "data": {
     *              "publicId": "1ee17100-c63f-6074-85c4-00155d01d845",
     *              "name": "Kollur Mookambika - Kundapura Rd, Herikudru, Karnataka",
     *              "streetName": "Kollur Mookambika - Kundapura Road",
     *              "streetNumber": "33",
     *              "postalCode": "123",
     *              "city": "Herikudru",
     *              "countryCode": "IN",
     *              "invitedRole": "property_admin",
     *              "invitedBy": "max o",
     *              "invitedAt": "2023-07-26T07:22:56+00:00"
     *          },
     *          "message": "Property Details"
     *      }
     *
     * ## Failed response ##
     * ### due to validation error
     *      {
     *        "data": "No data provided",
     *        "error": true,
     *        "message": "Invalid property"
     *      }
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
     *     summary="API end point to get property details of given id.",
     *     @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               type="object",
     *              @OA\Property(
     *                  property="role",
     *                  type="string",
     *                  default="",
     *                  example="janitor/property_admin"
     *              ),
     *              @OA\Property(
     *                  property="property",
     *                  type="string",
     *                  default="",
     *                  example="23eds-wZUFVcW1-bg532-di9NNG5rb-U4vWm5FWj"
     *              )
     *           )
     *       )
     *     ),
     * )
     * @OA\Tag(name="Property")
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param PropertyService $propertyService
     * @param LoggerInterface $requestLogger
     * @return View
     * @Route("/user-details", name="balu_property_user_detail", methods={"POST"})
     */
    public function userDetail(Request $request, GeneralUtility $generalUtility, PropertyService $propertyService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        try {
            $property = $em->getRepository(Property::class)->findOneBy(array('publicId' => $request->get('property'), 'deleted' => false));
            if (!$property instanceof Property) {
                throw new EntityNotFoundException('invalidProperty');
            }
            $propertyArray = $propertyService->generatePropertyUserDetails($property, $request, $this->locale);
            $data = $generalUtility->handleSuccessResponse('propertyDetails', $propertyArray);
        } catch (Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to check whether janitor is present of janitor is invited to this property
     *
     * # Request
     * ### Route /api/2.0/property/check-janitor-present/1ee17100-c63f-6074-85c4-00155d01d845
     * ## Success response ##
     *
     *      {
     *          "error": false,
     *          "data": {
     *          },
     *          "message": "Property Details"
     *      }
     *
     * ## Failed response ##
     * ### due to validation error
     *      {
     *        "data": "No data provided",
     *        "error": true,
     *        "message": "Invalid property"
     *      }
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
     *     description="Entity not found"
     * )
     * @Operation(
     *     summary="API end point to check whether janitor is present of janitor is invited to this property",
     *     @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               type="object",
     *              @OA\Property(
     *                  property="role",
     *                  type="string",
     *                  default="",
     *                  example="janitor/property_admin"
     *              ),
     *              @OA\Property(
     *                  property="property",
     *                  type="string",
     *                  default="",
     *                  example="23eds-wZUFVcW1-bg532-di9NNG5rb-U4vWm5FWj"
     *              )
     *           )
     *       )
     *     ),
     * )
     * @OA\Tag(name="Property")
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param PropertyService $propertyService
     * @param LoggerInterface $requestLogger
     * @param string|null $directory
     * @return View
     * @Route("/check-janitor-present/{property}/{directory}", defaults={"directory" = null}, name="balu_property_check_janitor_present", methods={"GET"})
     */
    public function checkJanitorPresentOrInvited(Request $request, GeneralUtility $generalUtility,
                                                 PropertyService $propertyService, LoggerInterface $requestLogger, ?string $directory = null): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        try {
            $property = $em->getRepository(Property::class)->findOneBy(array('publicId' => $request->get('property'), 'deleted' => false));
            if (!$property instanceof Property) {
                throw new EntityNotFoundException('invalidProperty');
            }
            $status = $propertyService->checkJanitorAvailableOrInvited($property, $directory);
            if (gettype($status) === 'string') {
                throw new CustomUserMessageAccountStatusException($status);
            }
            $data = $generalUtility->handleSuccessResponse('janitorNotPresent');
        } catch (EntityNotFoundException | CustomUserMessageAccountStatusException | Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }
}
