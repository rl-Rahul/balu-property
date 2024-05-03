<?php

/**
 * This file is part of the Balu Property package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Property;
use App\Service\UserService;
use App\Utils\GeneralUtility;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\View\View;
use App\Entity\UserIdentity;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use App\Entity\SubscriptionPlan;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Service\ObjectService;
use App\Entity\Apartment;
use App\Form\ApartmentType;
use App\Service\DamageService;
use App\Entity\PropertyUser;
use App\Entity\Role;
use Psr\Log\LoggerInterface;

/**
 * ObjectController
 *
 * Controller to manage object related actions.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 * @Route("/object")
 */
final class ObjectController extends BaseController
{
    /**
     * API end point to get objects of a property.
     *
     * # Response
     * ## Success response ##
     *       {
     *           "data": {
     *              {
     *                  "publicId": {
     *                      "uid": "1ece1708-6797-6518-a311-0242ac120004"
     *                  },
     *                  "name": "test",
     *                  "sortOrder": 1,
     *                  "roomCount": 2,
     *                  "officialNumber": 1,
     *                  "area": 123.0,
     *                  "objectType": "Apartment",
     *                  "floorNumber": "2",
     *                  "userCount": "0",
     *                  "hasActiveContract": false,
     *                  "activeContractType": "Ownership"
     *             },
     *           "error": false,
     *           "message": "List fetch success"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Data error"
     *       }
     * @Route("/list/{property}", name="balu_get_object", methods={"GET"})
     * @Operation(
     *      tags={"Object"},
     *      summary="API end point to get objects of a property.",
     *      @Security(name="Bearer"),
     *     @OA\Parameter(
     *      name="filter[text]",
     *      in="query",
     *      description="Text can be object name or type",
     *      @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *      name="showdisabled",
     *      in="query",
     *      description="Whether to show all or active properties",
     *      @OA\Schema(type="int")
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
     * @param string $property
     * @param Request $request
     * @param UserService $userService
     * @param ObjectService $objectService
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function list(string $property, Request $request, UserService $userService,
                         ObjectService $objectService, GeneralUtility $generalUtility, LoggerInterface $requestLogger): view
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $user = $this->getUser();
        try {
            if (!$property = $em->getRepository(Property::class)->findOneBy(['publicId' => $property, 'deleted' => false])) {
                throw new InvalidArgumentException('invalidProperty');
            }
            $isPropertyAdmin = $userService->isPropertyAdmin($property);
            $janitor = $em->getRepository(Property::class)->findOneBy(['publicId' => $property->getPublicId(), 'deleted' => false, 'janitor' => $user]);
            $propertyUser = $em->getRepository(PropertyUser::class)->findOneBy(['user' => $user, 'deleted' => false]);
            if (null == $janitor && null == $propertyUser && $property->getUser()->getId() != $user->getId() && $isPropertyAdmin === false) {
                throw new InvalidArgumentException('invalidObject');
            }
            $role = $em->getRepository(Role::class)->findOneBy(['roleKey' => $this->currentRole]);
            $apartment = $objectService->getObjects($property, $request, $this->locale, $this->getUser(), $role);
            if ($request->query->has('type') && $request->query->get('type') === 'message' &&
                $request->query->has('option') && $request->query->get('option') === 'hide') {
                foreach ($apartment as $key => $object) {
                    if (empty($object['tenants'])) {
                        unset($apartment[$key]);
                    }
                }
            }
            $apartment = array_values($apartment);
            $totalActiveObjectCount = $em->getRepository(Apartment::class)->getActiveApartmentCount($property->getIdentifier());
            $data = $generalUtility->handleSuccessResponse('listFetchSuccess', compact('apartment', 'totalActiveObjectCount'));
        } catch (ResourceNotFoundException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to create new object.
     *
     * # Request
     * In request body, system expects object details as JSON.
     * ## Example request to create new object
     *       {
     *           "name": "5",
     *           "sortOrder": 5,
     *           "officialNumber": 5,
     *           "roomCount": 5,
     *           "netRentRate": 5,
     *           "referenceRate": "1ece0e6b-c371-6e1a-9e28-00155d01d845",
     *           "additionalCostCurrency": "1ece0e69-29a6-68e6-bfef-00155d01d845",
     *           "baseIndexValue": "5",
     *           "netRentRateCurrency": "1ece0e69-29a6-68e6-bfef-00155d01d845",
     *           "additionalCost": 5,
     *           "area": 5,
     *           "volume": 5,
     *           "ceilingHeight": 5,
     *           "maxFloorLoading": 5,
     *           "landIndex": "1ece0e69-eae2-6cf8-bb9d-00155d01d845",
     *           "property": "1ece0e69-eae2-6cf8-bb9d-00155d01d845",
     *           "contractType": "1ece15fa-4908-60e8-81c8-00155d01d845",
     *           "floorNumber": "1ece16b0-bd43-6d2a-b1f2-00155d01d845",
     *           "objectType": "1ece168f-d5c1-64d6-8c51-00155d01d845",
     *           "baseIndexDate": "2022-06-14T05:10:00.000Z",
     *           "amenity": [
     *               {
     *                   "id": "1ece0e67-9cd3-6ce4-b8e6-00155d01d845",
     *                   "value": "5"
     *               }
     *           ],
     *           "modeOfPayment": "1ece0e6a-a802-6d92-b1b6-00155d01d845",
     *           "documents": ["1ecb66da-d42e-6784-bad1-0242ac120003", "1ec79eba-3ad0-6be6-b1b4-0242ac120004"],
     *           "floorPlan": ["1ecb66da-d42e-6784-bad1-0242ac120003", "1ec79eba-3ad0-6be6-b1b4-0242ac120004"],
     *           "coverImage": ["1ec79eba-3ad0-6be6-b1b4-0242ac120004"]
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "objectCreateFail"
     *       }
     * @Route("/new", name="balu_create_object", methods={"POST"})
     * @Operation(
     *      tags={"Object"},
     *      summary="API end point to create new object.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="name", type="string", default="", example="Hellen"),
     *               @OA\Property(property="sortOrder", type="int", default="", example="1"),
     *               @OA\Property(property="officialNumber", type="string", default="", example="1995"),
     *               @OA\Property(property="contractType", type="string", default="", example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"),
     *               @OA\Property(property="floorNumber", type="string", default="", example="Las vegas"),
     *               @OA\Property(property="objectType", type="string", default="", example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"),
     *               @OA\Property(property="property", type="string", default="", example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"),
     *               @OA\Property(property="amenity", type="object",
     *                   @OA\Property(
     *                          property="id",
     *                          type="string",
     *                          default="",
     *                          example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"
     *                      ),
     *                      @OA\Property(
     *                          property="value",
     *                          type="string",
     *                          default="",
     *                          example="1"
     *                      )
     *               ),
     *               @OA\Property(property="actualIndexStand", type="string", default="", example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"),
     *               @OA\Property(property="referenceRate", type="string", default="", example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"),
     *               @OA\Property(property="rent", type="string", default="", example="online"),
     *               @OA\Property(property="additionalCost", type="string", default="", example=""),
     *               @OA\Property(property="roomCount", type="int", default="", example="1"),
     *               @OA\Property(property="landIndex", type="string", default="", example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"),
     *               @OA\Property(property="modeOfPayment", type="string", default="", example=""),
     *               @OA\Property(property="area", type="string", default="", example=""),
     *               @OA\Property(property="netRentRate", type="int", default="", example=""),
     *               @OA\Property(property="netRentRateCurrency", type="int", default="", example=""),
     *               @OA\Property(property="baseIndexDate", type="date", default="", example=""),
     *               @OA\Property(property="baseIndexValue", type="int", default="", example=""),
     *               @OA\Property(property="totalObjectValue", type="int", default="", example=""),
     *               @OA\Property(property="additionalCostBuilding", type="int", default="", example=""),
     *               @OA\Property(property="additionalCostHeating", type="int", default="", example=""),
     *               @OA\Property(property="additionalCostEnvironment", type="int", default="", example=""),
     *               @OA\Property(property="additionalCostElevator", type="int", default="", example=""),
     *               @OA\Property(property="additionalCostParking", type="int", default="", example=""),
     *               @OA\Property(property="additionalCostRenewal", type="int", default="", example=""),
     *               @OA\Property(property="additionalCostMaintenance", type="int", default="", example=""),
     *               @OA\Property(property="additionalCostAdministration", type="int", default="", example=""),
     *               @OA\Property(property="additionalCostCurrency", type="string", default="", example=""),
     *               @OA\Property(
     *                      property="documents",
     *                      type="array",
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003"),
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003")
     *               ),
     *              @OA\Property(
     *                      property="floorPlan",
     *                      type="array",
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003"),
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003")
     *               ),
     *               @OA\Property(
     *                      property="coverImage",
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
     * @param ObjectService $objectService
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function create(Request $request, ObjectService $objectService, GeneralUtility $generalUtility, LoggerInterface $requestLogger): view
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            $aObject = $objectService->validateData($request->get('property'));
            $object = new Apartment();
            $form = $this->createNamedForm(ApartmentType::class, $object);
            $form->handleRequest($request);
            if (!$form->isSubmitted() || !$form->isValid()) {
                throw new \Exception('formError');
            }
            $apartment = $objectService->saveObjectInfo($aObject['property'], $object, $request, $this->getUser(), $form);
            $apartment->setProperty($aObject['property']);
            $objectService->setObjectStatus($apartment);
            $em->flush();
            $em->getConnection()->commit();
            $data = $generalUtility->handleSuccessResponse('objectSuccess', ['id' => $apartment->getPublicId()]);
        } catch (ResourceNotFoundException | \Exception $e) {
            $em->rollback();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to edit an object.
     * # Request
     * In request body, system expects object details as JSON.
     * ## Example request to edit an object
     *      {
     *           "name": "5",
     *           "sortOrder": 5,
     *           "officialNumber": 5,
     *           "roomCount": 5,
     *           "netRentRate": 5,
     *           "referenceRate": "1ece0e6b-c371-6e1a-9e28-00155d01d845",
     *           "property": "1ece0e6b-c371-6e1a-9e28-00155d01d845",
     *           "additionalCostCurrency": "1ece0e69-29a6-68e6-bfef-00155d01d845",
     *           "baseIndexValue": "5",
     *           "netRentRateCurrency": "1ece0e69-29a6-68e6-bfef-00155d01d845",
     *           "additionalCost": 5,
     *           "area": 5,
     *           "volume": 5,
     *           "ceilingHeight": 5,
     *           "maxFloorLoading": 5,
     *           "landIndex": "1ece0e69-eae2-6cf8-bb9d-00155d01d845",
     *           "contractType": "1ece15fa-4908-60e8-81c8-00155d01d845",
     *           "floorNumber": "1ece16b0-bd43-6d2a-b1f2-00155d01d845",
     *           "objectType": "1ece168f-d5c1-64d6-8c51-00155d01d845",
     *           "baseIndexDate": "2022-06-14T05:10:00.000Z",
     *           "amenity": [
     *               {
     *                   "id": "1ece0e67-9cd3-6ce4-b8e6-00155d01d845",
     *                   "value": "5"
     *               }
     *           ],
     *           "modeOfPayment": "1ece0e6a-a802-6d92-b1b6-00155d01d845",
     *           "documents": ["1ecb66da-d42e-6784-bad1-0242ac120003", "1ec79eba-3ad0-6be6-b1b4-0242ac120004"],
     *           "floorPlan": ["1ecb66da-d42e-6784-bad1-0242ac120003", "1ec79eba-3ad0-6be6-b1b4-0242ac120004"]
     *       }
     * # Response
     * ## Success response ##
     *       {
     *           "data": {
     *              "id": "1ecd66fc-b875-6226-80b0-0242ac120004"
     *           },
     *           "error": false,
     *           "message": "objectSuccess"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "propertyCreateFail"
     *       }
     * @Route("/edit/{object}", name="balu_edit_object", methods={"POST"})
     * @Operation(
     *      tags={"Object"},
     *      summary="API end point to edit an object.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="name", type="string", default="", example="Hellen"),
     *               @OA\Property(property="sortOrder", type="int", default="", example="1"),
     *               @OA\Property(property="officialNumber", type="string", default="", example="1995"),
     *               @OA\Property(property="contractType", type="string", default="", example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"),
     *               @OA\Property(property="floorNumber", type="string", default="", example="Las vegas"),
     *               @OA\Property(property="objectType", type="string", default="", example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"),
     *               @OA\Property(property="property", type="string", default="", example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"),
     *               @OA\Property(property="amenity", type="object",
     *                   @OA\Property(
     *                          property="id",
     *                          type="string",
     *                          default="",
     *                          example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"
     *                      ),
     *                      @OA\Property(
     *                          property="value",
     *                          type="string",
     *                          default="",
     *                          example="1"
     *                      )
     *               ),
     *               @OA\Property(property="actualIndexStand", type="string", default="", example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"),
     *               @OA\Property(property="referenceRate", type="string", default="", example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"),
     *               @OA\Property(property="rent", type="string", default="", example="online"),
     *               @OA\Property(property="additionalCost", type="string", default="", example=""),
     *               @OA\Property(property="roomCount", type="int", default="", example="1"),
     *               @OA\Property(property="landIndex", type="string", default="", example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"),
     *               @OA\Property(property="modeOfPayment", type="string", default="", example=""),
     *               @OA\Property(property="area", type="string", default="", example=""),
     *               @OA\Property(property="netRentRate", type="int", default="", example=""),
     *               @OA\Property(property="netRentRateCurrency", type="int", default="", example=""),
     *               @OA\Property(property="baseIndexDate", type="date", default="", example=""),
     *               @OA\Property(property="baseIndexValue", type="int", default="", example=""),
     *               @OA\Property(property="totalObjectValue", type="int", default="", example=""),
     *               @OA\Property(property="additionalCostBuilding", type="int", default="", example=""),
     *               @OA\Property(property="additionalCostHeating", type="int", default="", example=""),
     *               @OA\Property(property="additionalCostEnvironment", type="int", default="", example=""),
     *               @OA\Property(property="additionalCostElevator", type="int", default="", example=""),
     *               @OA\Property(property="additionalCostParking", type="int", default="", example=""),
     *               @OA\Property(property="additionalCostRenewal", type="int", default="", example=""),
     *               @OA\Property(property="additionalCostMaintenance", type="int", default="", example=""),
     *               @OA\Property(property="additionalCostAdministration", type="int", default="", example=""),
     *               @OA\Property(property="additionalCostCurrency", type="string", default="", example=""),
     *               @OA\Property(
     *                      property="documents",
     *                      type="array",
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003"),
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003")
     *               ),
     *              @OA\Property(
     *                      property="floorPlan",
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
     * @param string $object
     * @param Request $request
     * @param ObjectService $objectService
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function edit(string $object, Request $request, ObjectService $objectService, GeneralUtility $generalUtility, LoggerInterface $requestLogger): view
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            $user = $this->getUser();
            $aObject = $objectService->validateData($request->get('property'), $object);
            $form = $this->createNamedForm(ApartmentType::class, $aObject['object']);
            $form->handleRequest($request);
            if (!$form->isSubmitted() || !$form->isValid()) {
                throw new \Exception('formError');
            }
            $apartment = $objectService->saveObjectInfo($aObject['property'], $aObject['object'], $request, $user, $form, true);
            $em->flush();
            $em->getConnection()->commit();
            $data = $generalUtility->handleSuccessResponse('objectEditSuccess', ['id' => $apartment->getPublicId()]);
        } catch (ResourceNotFoundException | \Exception $e) {
            $em->rollback();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to delete an object.
     *
     * # Request
     * # Response
     * ## Success response ##
     *       {
     *           "data": {
     *           },
     *           "error": false,
     *           "message": "Object deleted successfully"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "object not found"
     *       }
     * @Route("/{property}/{object}", name="balu_delete_object", methods={"DELETE"})
     * @Operation(
     *      tags={"Object"},
     *      summary="API end point to delete an object.",
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
     * @param string $property
     * @param string $object
     * @param Request $request
     * @param ObjectService $objectService
     * @param GeneralUtility $generalUtility
     * @return View
     */
    public function delete(string $property, string $object, Request $request, ObjectService $objectService, GeneralUtility $generalUtility, UserService $userService, LoggerInterface $requestLogger): view
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            $aObject = $objectService->validateData($property, $object);
            $objectService->deleteObject($aObject['object'], $this->locale);
            $em->flush();
            $em->getConnection()->commit();
            $data = $generalUtility->handleSuccessResponse('objectDeleteSuccess');
        } catch (ResourceNotFoundException | \Exception $e) {
            $em->rollback();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to get single object detail.
     *
     * # Response
     * ## Success response ##
     *       {
     *             "data": {
     *                  "publicId": "1ece26b6-c3f4-6df2-8664-0242ac120004",
     *                  "objectType": {
     *                      "id": "1ecd2886-5eae-6d8c-b623-0242ac120004",
     *                      "name": "Apartment"
     *                  },
     *                  "officialNumber": 1,
     *                  "objectNumber": 1,
     *                  "floor": {
     *                      "number": "2",
     *                      "id": "1ecc51de-22b8-6294-a0ac-0242ac120004"
     *                  },
     *                  "area": 123.0,
     *                  "roomCount": "2",
     *                  "name": "test",
     *                  "userCount": "2",
     *                  "amenities": [
     *                      {
     *                          "publicId": "1ecd5b1a-dc20-6164-ad5b-0242ac120004",
     *                          "value": 1.0,
     *                          "name": "Balcony / Terrace / Loggia",
     *                          "key": "bal",
     *                          "isInput": true
     *                      }
     *                  ],
     *                  "modeOfPayment": {
     *                      "identifier": 1,
     *                      "public_id": "1ecd4e57-bf0f-6a68-a2e1-0242ac120004",
     *                      "created_at": "2022-05-16T06:57:42+00:00",
     *                      "deleted": false,
     *                      "name_en": "online",
     *                      "name_de": "online"
     *                  },
     *                  "additionalCost": "2",
     *                  "netRentRate": 100.0,
     *                  "netRentRateCurrency": "1ecd4e52-b69a-63c4-aa49-0242ac120004",
     *                  "baseIndexDate": "2022-05-10T05:57:00.000Z",
     *                  "baseIndexValue": 1000.0,
     *                  "additionalCostCurrency": "1ecd4e52-b69a-63c4-aa49-0242ac120004",
     *                  "contractType": {
     *                      "name": "Rental",
     *                      "publicId": "1ecc51de-22b2-6f10-97d2-0242ac120004"
     *                  }
     *          },
     *         "error": false,
     *         "message": "Detail fetched successfully"
     *    }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Object not found"
     *       }
     * @Route("/detail/{property}/{object}", name="balu_single_object", methods={"GET"})
     * @Operation(
     *      tags={"Object"},
     *      summary="API end point to get single object detail.",
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
     * @param string $property
     * @param string $object
     * @param Request $request
     * @param ObjectService $objectService
     * @param GeneralUtility $generalUtility
     * @param UserService $userService
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function detail(string $property, string $object, Request $request, ObjectService $objectService, GeneralUtility $generalUtility, UserService $userService, LoggerInterface $requestLogger): view
    {
        $curDate = new \DateTime('now');
        try {
            $aObject = $objectService->validateData($property, $object);
            $data = $generalUtility->handleSuccessResponse('detailFetchSuccess', $objectService->getObjectDetail($aObject['object'], $this->locale, $request));
        } catch (ResourceNotFoundException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to get floor plan
     *
     *
     * # Request
     * System expects ticket ID as a Path parameter.
     *
     * ## Success response ##
     *             {
     *              "currentRole": "owner",
     *              "data": {
     *                  "floorPlan": [
     *                      {
     *                          "identifier": 8,
     *                          "publicId": "1ecf92c8-1ee6-61ce-b0b5-0242ac130003",
     *                          "originalName": "screenshot20220614at111055am-165667295362bed2b9b098a.png",
     *                          "path": "http://localhost:8001/files/property/folder-165667300962bed2f115a9a/screenshot20220614at111055am-165667295362bed2b9b098a.png",
     *                          "displayName": "Screenshot 2022-06-14 at 11.10.55 AM.png",
     *                          "type": "floorPlan",
     *                          "filePath": "/var/www/app/balu/files/property/folder-165579306262b165a63c900/folder-165667300962bed2f115a9a/screenshot20220614at111055am-165667295362bed2b9b098a.png",
     *                          "isPrivate": "public",
     *                          "mimeType": "image/png",
     *                          "size": 35928.0,
     *                          "folder": "1ecf92c8-1aa3-6dd2-a2fa-0242ac130003"
     *                      },
     *                      {
     *                          "identifier": 9,
     *                          "publicId": "1ecf92c8-2873-6124-9975-0242ac130003",
     *                          "originalName": "screenshot20220614at111055am-165667299862bed2e617104.png",
     *                          "path": "http://localhost:8001/files/property/folder-165667300962bed2f115a9a/screenshot20220614at111055am-165667299862bed2e617104.png",
     *                          "displayName": "Screenshot 2022-06-14 at 11.10.55 AM.png",
     *                          "type": "floorPlan",
     *                          "filePath": "/var/www/app/balu/files/property/folder-165579306262b165a63c900/folder-165667300962bed2f115a9a/screenshot20220614at111055am-165667299862bed2e617104.png",
     *                          "isPrivate": "public",
     *                          "mimeType": "image/png",
     *                          "size": 35928.0,
     *                          "folder": "1ecf92c8-1aa3-6dd2-a2fa-0242ac130003"
     *                      }
     *                  ]
     *              },
     *              "error": false,
     *              "message": "Floor plan fetched successfully"
     *             }
     * @Operation(
     *      tags={"Object"},
     *      summary="API end point get all users related to a ticket",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="ticketId", type="string", default="", example="1ecdb203-7397-6490-b46f-0242ac1b0004"),
     *          )
     *       )
     *     ),
     *  @OA\Response(
     *     response=200,
     *     description="Returns the floor plan of an object",
     *  ),
     *  @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     *  ),
     *  @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     *  ),
     * )
     * @param string $object
     * @param Request $request
     * @param DamageService $damageService
     * @param GeneralUtility $generalUtility
     * @param ObjectService $objectService
     * @param LoggerInterface $requestLogger
     * @return View
     * @Route("/get-floor-plan/{object}", name="balu_floor_plan", methods={"GET"})
     */
    public function getFloorPlan(string $object, Request $request, DamageService $damageService, GeneralUtility $generalUtility, ObjectService $objectService, LoggerInterface $requestLogger): view
    {
        $curDate = new \DateTime('now');
        try {
            $em = $this->doctrine->getManager();
            $object = $em->getRepository(Apartment::class)->findOneBy(['publicId' => $object, 'deleted' => 0]);
            $damageService->validatePermission($request, $this->currentRole, $this->getUser(), $object);
            $data = $generalUtility->handleSuccessResponse('detailFetchSuccess', $objectService->getFloorPlan($object, $request));
        } catch (ResourceNotFoundException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to get list of all users related to given objects
     *
     *
     * # Request
     * System expects object id array as a Path parameter.
     *
     * ## Success response ##
     *
     *       {
     *           "data": [
     *               {
     *                   "publicId": "1ece257d-4781-6bb0-a5c1-0242ac170003",
     *                   "firstName": "Tenant",
     *                   "lastName": "Example",
     *                   "companyName": "Company name",
     *                   "email": "tenant@example.com",
     *                   "deviceId": ["1ec7c7900155d01d845"],
     *                   "apartment": {
     *                       "publicId": "1ece2575-db82-69ca-aed8-0242ac170003",
     *                       "name": "Apartment 1"
     *                   }
     *               },
     *               {
     *                   "publicId": "1ec7c797-dc02-6a18-a18b-00155d01d845",
     *                   "firstName": "Janitor",
     *                   "lastName": "Example",
     *                   "email": "marcel-dellner@gmx.net",
     *                   "deviceId": ["1ec7c7900155d01h8447","1ec7c7900155d01d845"],
     *                   "apartment": {
     *                       "publicId": "1ece2575-db82-69ca-aed8-0242ac170003",
     *                       "name": "Apartment 2"
     *                   }
     *               },
     *               {
     *                   "publicId": "1ec7c797-e4f3-6e4c-bbdf-00155d01d845",
     *                   "firstName": "David",
     *                   "lastName": "Neff",
     *                   "companyName": "Eigenmann AG",
     *                   "email": "propertyadmin@example.com",
     *                   "deviceId": ["1ec7c7900155d01d244"],
     *                   "apartment": {
     *                       "publicId": "1ece2575-db82-69ca-aed8-0242ac170003",
     *                       "name": "Apartment 5"
     *                   }
     *               },
     *               {
     *                   "publicId": "1ec8e27e-3f54-6538-9ba4-00155d01d845",
     *                   "firstName": "Robert",
     *                   "lastName": "Abc",
     *                   "companyName": "we",
     *                   "email": "onwer@example.com",
     *                   "deviceId": ["1ec734155d01d234"],
     *                   "apartment": {
     *                       "publicId": "1ece2575-db82-69ca-aed8-0242ac170003",
     *                       "name": "Apartment 5"
     *                   }
     *               }
     *           ],
     *           "error": false,
     *           "message": "success"
     *       }
     * @Operation(
     *      tags={"Object"},
     *      summary="API end point get all users related to given objects",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="objectId", type="string", default="", example="1ecdb203-7397-6490-b46f-0242ac1b0004"),
     *          )
     *       )
     *     ),
     *  @OA\Response(
     *     response=200,
     *     description="Returns list of all users related to a ticket",
     *  ),
     *  @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     *  ),
     *  @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     *  ),
     *  @OA\Tag(name="Ticket")
     * )
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param ObjectService $objectService
     * @param LoggerInterface $requestLogger
     * @return View
     * @Route("/users", name="balu_objectt_users", methods={"GET"})
     */
    public function users(Request $request, GeneralUtility $generalUtility, ObjectService $objectService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        try {
            $objectIds = $request->get('object');
            $data = $generalUtility->handleSuccessResponse('success', $objectService->getObjectUsers($objectIds));
        } catch (ResourceNotFoundException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }


    /**
     * API end point to activate selected objects.
     * # Request
     * In request body, system expects object details as JSON.
     * ## Example request to edit an object
     *      {
     *           "objects": ["1ecb66da-d42e-6784-bad1-0242ac120003", "1ec79eba-3ad0-6be6-b1b4-0242ac120004"]
     *      }
     * # Response
     * ## Success response ##
     *       {
     *           "data": [],
     *           "error": false,
     *           "message": "objectActivationSuccess"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "propertyCreateFail"
     *       }
     * @Route("/activate/{property}", name="balu_activate_object", methods={"POST"})
     * @Operation(
     *      tags={"Object"},
     *      summary="API end point to activate objects.",
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
     * @param string $property
     * @param Request $request
     * @param ObjectService $objectService
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function activateObjects(string $property, Request $request, ObjectService $objectService, GeneralUtility $generalUtility, LoggerInterface $requestLogger): view
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            $aObject = $objectService->validateData($property, null);
            $objectService->activateObjects($aObject['property'], $request);
            $em->flush();
            $role = $em->getRepository(Role::class)->findOneBy(['roleKey' => $this->currentRole]);
            $apartment = $objectService->getObjects($aObject['property'], $request, $this->locale, $this->getUser(), $role);
            $em->getConnection()->commit();
            $data = $generalUtility->handleSuccessResponse('objectActivationSuccess', $apartment);
        } catch (ResourceNotFoundException | \Exception $e) {
            $em->rollback();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to request object reset.
     * # Request
     * type can be activate or deactivate
     * In request body, system expects object details as JSON.
     * ## Example request to edit an object
     *   {
     *       "objects":["1edc878e-8dc5-6ac8-a1b1-34735ae6e2bb", "1edc878e-544d-6fe8-8959-34735ae6e2bb"],
     *       "reason":"error",
     *       "property": "1ed8cde8-40e7-6354-bbaa-5254a2026859"
     *   }
     * # Response
     * ## Success response ##
     *       {
     *           "data": [],
     *           "error": false,
     *           "message": "objectResetRequestSuccess"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "propertyCreateFail"
     *       }
     * @Route("/reset/{type}", name="balu_reset_request_object", methods={"POST"})
     * @Operation(
     *      tags={"Object"},
     *      summary="API end point to request objects reset.",
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
     * @param string $type
     * @param Request $request
     * @param ObjectService $objectService
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param ParameterBagInterface $parameterBag
     * @return View
     */
    public function resetRequest(string $type, Request $request, ObjectService $objectService, GeneralUtility $generalUtility,
                                 LoggerInterface $requestLogger, ParameterBagInterface $parameterBag): view
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            $property = $em->getRepository(Property::class)->findOneBy(['publicId' => $request->request->get('property')]);
            if (!$property instanceof Property) {
                throw new InvalidArgumentException('invalidProperty');
            }
            if (!in_array($type, ['activate', 'deactivate'])) {
                throw new InvalidArgumentException('invalidType');
            }
            $updatedResetCount = $property->getResetCount() + 1;
            $property->setResetCount($updatedResetCount);
            $aObject = $objectService->validateData($request->get('property'));
            $objectService->requestObjectReset($type, $aObject['property'], $this->getUser(), $request);
            $em->flush();
            $em->getConnection()->commit();
            if ($type == 'deactivate') {
                $data = $generalUtility->handleSuccessResponse('objectDeactivateSuccess');
            } else {
                $data = $generalUtility->handleSuccessResponse('objectResetSuccess');
            }
        } catch (ResourceNotFoundException | InvalidArgumentException | \Exception $e) {
            $em->rollback();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }
}
