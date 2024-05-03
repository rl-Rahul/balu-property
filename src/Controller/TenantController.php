<?php

/**
 * This file is part of the Balu Property package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\ObjectContracts;
use App\Entity\Property;
use App\Service\UserService;
use App\Utils\GeneralUtility;
use Doctrine\ORM\EntityNotFoundException;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\View\View;
use App\Entity\UserIdentity;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Service\TenantService;
use App\Entity\Apartment;
use App\Form\ObjectContractType;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;

/**
 * TenantController
 *
 * Controller to manage object related actions.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 * @Route("/tenant")
 */
final class TenantController extends BaseController
{
    /**
     * API end point to add a contract to object.
     *
     * # Request
     * In request body, system expects object details as JSON.
     * Pass object_owner or tenant in role key as required
     * In tenants array pass corresponding api from users api response
     *
     * ## Example request to create new contract
     *       {
     *           "startDate": "1995-12-22",
     *           "endDate": "1995-12-22",
     *           "active": "1",
     *           "ownerVote":false,
     *           "noticePeriod":"1ecc51de-22c3-6f22-a4bc-0242ac120004",
     *           "contractPeriodType": "1ecc51de-22c3-6f22-a4bc-0242ac120004",
     *           "object":"1ecc51de-22c3-6f22-a4bc-0242ac120004",
     *           "property":"1ecc51de-22c3-6f22-a4bc-0242ac120004",
     *           "tenants": [
     *               {
     *                   "id": "1ecdd0c9-5eba-6b50-8aee-00155d01d845",
     *                   "role": "object_owner"
     *               },
     *               {
     *                   "id": "1ecdffde-6b95-675a-abee-00155d01d845",
     *                   "role": "company"
     *               }
     *           ],
     *           "documents": ["1ecb66da-d42e-6784-bad1-0242ac120003", "1ec79eba-3ad0-6be6-b1b4-0242ac120004"]
     *       }
     * # Response
     * ## Success response ##
     *       {
     *          "data": [
     *              'id': ""
     *          ],
     *          "error": false,
     *          "message": "Contract added successfully"
     *        }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "propertyCreateFail"
     *       }
     * @Route("/new", name="balu_create_tenant", methods={"POST"})
     * @Operation(
     *      tags={"Contract"},
     *      summary="API end point to add a contract.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="tenants", type="object",
     *                   @OA\Property(
     *                          property="id",
     *                          type="string",
     *                          default="",
     *                          example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"
     *                      ),
     *                      @OA\Property(
     *                          property="role",
     *                          type="string",
     *                          default="",
     *                          example="company"
     *                      )
     *               ),
     *               @OA\Property(property="contractPeriodType", type="string", default="", example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"),
     *               @OA\Property(property="additionalComment", type="string", default="", example="1"),
     *               @OA\Property(property="role", type="date", default="", example="object_owner"),
     *               @OA\Property(property="noticePeriod", type="string", default="", example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"),
     *               @OA\Property(property="object", type="string", default="", example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"),
     *               @OA\Property(property="property", type="string", default="", example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"),
     *               @OA\Property(property="ownerVote", type="bool", default="", example=""),
     *               @OA\Property(property="endDate", type="date", default="", example="1995-12-22"),
     *               @OA\Property(property="startDate", type="date", default="", example="1995-12-22"),
     *               @OA\Property(
     *                      property="documents",
     *                      type="array",
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003"),
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003")
     *               ),
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
     * @param UserService $userService
     * @param TenantService $tenantService
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function create(Request $request, UserService $userService, TenantService $tenantService,
                           GeneralUtility $generalUtility, LoggerInterface $requestLogger): view
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            $aObject = $this->validateData($userService, $request->get('property'), $request->get('object'));
            $contract = new ObjectContracts();
            $form = $this->createNamedForm(ObjectContractType::class, $contract);
            $form->handleRequest($request);
            if (!$form->isSubmitted() || !$form->isValid()) {
                return $this->response($generalUtility->handleFailedResponse('formError', 400, null, $this->getErrorsFromForm($form)));
            }
            $contract = $tenantService->saveContract($aObject['property'], $aObject['object'], $contract, $request, $this->getUser(), $this->locale, $this->currentRole);
            $em->flush();
            $em->getConnection()->commit();
            $data = $generalUtility->handleSuccessResponse('contactCreateSuccess', ['id' => $contract->getPublicId()]);
        } catch (ResourceNotFoundException | \Exception $e) {
            $em->rollback();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to edit a contract.
     * ### api/2.0/tenant/1eccd121-13bf-61bc-b2a9-00155d01d845/1ecd2b01-752c-6e24-abd7-00155d01d845/1ecdc225-fc37-6656-b792-00155d01d845
     * # Request
     * In request body, system expects object details as JSON.
     * ## Example request to edit a contract
     *       {
     *           "startDate": "1995-12-22",
     *           "endDate": "1995-12-22",
     *           "active": "1",
     *           "ownerVote":false,
     *           "noticePeriod":"1ecc51de-22c3-6f22-a4bc-0242ac120004",
     *           "object":"1ecc51de-22c3-6f22-a4bc-0242ac120004",
     *           "property":"1ecc51de-22c3-6f22-a4bc-0242ac120004",
     *           "tenants": [
     *               {
     *                   "id": "1ecdd0c9-5eba-6b50-8aee-00155d01d845",
     *                   "role": "object_owner"
     *               },
     *               {
     *                   "id": "1ecdffde-6b95-675a-abee-00155d01d845",
     *                   "role": "company"
     *               }
     *           ],
     *           "documents": ["1ecb66da-d42e-6784-bad1-0242ac120003", "1ec79eba-3ad0-6be6-b1b4-0242ac120004"]
     *       }
     * # Response
     * ## Success response ##
     *       {
     *          "data": [],
     *          "error": false,
     *          "message": "Contract edited successfully"
     *        }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Invalid object"
     *       }
     * @Route("/edit/{contract}", name="balu_edit_contract", methods={"POST"})
     * @Operation(
     *      tags={"Contract"},
     *      summary="API end point to edit a contract.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="tenants", type="object",
     *                   @OA\Property(
     *                          property="id",
     *                          type="string",
     *                          default="",
     *                          example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"
     *                      ),
     *                      @OA\Property(
     *                          property="role",
     *                          type="string",
     *                          default="",
     *                          example="company"
     *                      )
     *               ),
     *               @OA\Property(property="contractPeriodType", type="string", default="", example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"),
     *               @OA\Property(property="additionalComment", type="string", default="", example="1"),
     *               @OA\Property(property="role", type="date", default="", example="object_owner"),
     *               @OA\Property(property="noticePeriod", type="string", default="", example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"),
     *               @OA\Property(property="object", type="string", default="", example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"),
     *               @OA\Property(property="property", type="string", default="", example="1ec92e2e-f39b-6b76-9a7a-0242ac120004"),
     *               @OA\Property(property="ownerVote", type="bool", default="", example=""),
     *               @OA\Property(property="endDate", type="date", default="", example="1995-12-22"),
     *               @OA\Property(property="startDate", type="date", default="", example="1995-12-22"),
     *               @OA\Property(
     *                      property="documents",
     *                      type="array",
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003"),
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003")
     *               ),
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
     * @param string $contract
     * @param Request $request
     * @param UserService $userService
     * @param TenantService $tenantService
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function edit(string $contract, Request $request, UserService $userService, TenantService $tenantService,
                         GeneralUtility $generalUtility, LoggerInterface $requestLogger): view
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            $aObject = $this->validateData($userService, $request->get('property'), $request->get('object'), $contract);
            $form = $this->createNamedForm(ObjectContractType::class, $aObject['contract']);
            $form->handleRequest($request);
            if (!$form->isSubmitted() || !$form->isValid()) {
                return $this->response($generalUtility->handleFailedResponse('formError', 400, null, $this->getErrorsFromForm($form)));
            }
            $tenantService->editContract($aObject['property'], $aObject['object'], $aObject['contract'], $request,
                $this->getUser(), $this->locale, $this->currentRole);
            $em->flush();
            $em->getConnection()->commit();
            $data = $generalUtility->handleSuccessResponse('contactEditSuccess', []);
        } catch (ResourceNotFoundException | \Exception  $e) {
            $em->rollback();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to get all contract to object.
     * # Response
     * ## Success response ##
     *       {
     *           "data": [
     *               {
     *                   "publicId": {
     *                       "uid": "1ece26d8-e78c-6626-b271-0242ac120004"
     *                   },
     *                   "active": true,
     *                   "endDate": "1995-12-22T00:00:00+00:00",
     *                   "additionalComment": "test",
     *                   "noticePeriod": {
     *                       "uid": "1ecc51de-22c3-6f22-a4bc-0242ac120004"
     *                   },
     *                   "noticePeriodName": "1 month",
     *                   "contractType": "Rental Contract",
     *                   "tenants": [
     *                       {
     *                           "roleName": "Tenant",
     *                           "role": "tenant",
     *                           "publicId": {
     *                               "uid": "1ecc51de-292a-63fc-8c7b-0242ac120004"
     *                           },
     *                           "name": "Test User"
     *                       },
     *                       {
     *                           "roleName": "Tenant",
     *                           "role": "tenant",
     *                           "publicId": {
     *                               "uid": "1ecdceb4-84b8-68b4-9bac-0242ac120004"
     *                           },
     *                           "name": "vr vr"
     *                       }
     *                   ]
     *               }
     *           ],
     *          "error": false,
     *          "message": "Data fetched"
     *        }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Data error"
     *       }
     * @Route("/list/{property}/{object}", name="balu_list_contract", methods={"GET"})
     * @Operation(
     *      tags={"Contract"},
     *      summary="API end point to add a contract.",
     *      @Security(name="Bearer"),
     *     ),
     * @OA\Response(
     *         response="200",
     *         description="Returned on successful response"
     *     ),
     * @OA\Response(
     *         response="400",
     *         description="Returned when request is not valid"
     *     ),
     * @OA\Response(
     *          response="401",
     *          description="User not authenticated"
     *     ),
     * @OA\Response(
     *         response="500",
     *         description="Internal Error"
     *     )
     *)
     * @param string $property
     * @param string $object
     * @param Request $request
     * @param UserService $userService
     * @param TenantService $tenantService
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function list(string $property, string $object, Request $request, UserService $userService, TenantService $tenantService,
                         GeneralUtility $generalUtility, LoggerInterface $requestLogger): view
    {
        $curDate = new \DateTime('now');
        try {
            $aObject = $this->validateData($userService, $property, $object);
            $list = $tenantService->listContracts($aObject['object'], $request, $this->locale, $this->getUser());
            $data = $generalUtility->handleSuccessResponse('listFetchSuccess', $list);
        } catch (ResourceNotFoundException | \Exception  $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }
        return $this->response($data);
    }


    /**
     * API end point to get details of a contract.
     * # Response
     * ## Success response ##
     *       {
     *           "publicId": "1ece26d8-e78c-6626-b271-0242ac120004",
     *           "objectType": {
     *               "id": "1ecd2886-5eae-6d8c-b623-0242ac120004",
     *               "name": "Apartment"
     *           },
     *           "additionalComment": "test",
     *           "active": true,
     *           "endDate": "1995-12-22T00:00:00+00:00",
     *           "noticePeriod": {
     *               "id": "1ecc51de-22c3-6f22-a4bc-0242ac120004",
     *               "name": "1 month"
     *           },
     *           "rentalType": "",
     *           "tenants": [
     *               {
     *                   "roleName": "Tenant",
     *                   "role": "tenant",
     *                   "publicId": {
     *                       "uid": "1ecc51de-292a-63fc-8c7b-0242ac120004"
     *                   },
     *                   "name": "Test User"
     *               },
     *               {
     *                   "roleName": "Tenant",
     *                   "role": "tenant",
     *                   "publicId": {
     *                       "uid": "1ecdceb4-84b8-68b4-9bac-0242ac120004"
     *                   },
     *                   "name": "vr vr"
     *               }
     *           ],
     *          },
     *           "error": false,
     *           "message": "Data fetched"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "propertyCreateFail"
     *       }
     * @Route("/contract-detail/{property}/{object}/{contract}", name="balu_contract_detail", methods={"GET"})
     * @Operation(
     *      tags={"Contract"},
     *      summary="API end point to get details of a contract"
     *     ),
     * @OA\Response(
     *         response="200",
     *         description="Returned on successful response"
     *     ),
     * @OA\Response(
     *         response="400",
     *         description="Returned when request is not valid"
     *     ),
     * @OA\Response(
     *          response="401",
     *          description="User not authenticated"
     *     ),
     * @OA\Response(
     *         response="500",
     *         description="Internal Error"
     *     )
     *)
     * @param string $property
     * @param string $object
     * @param string $contract
     * @param Request $request
     * @param UserService $userService
     * @param TenantService $tenantService
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function getContractDetail(string $property, string $object, string $contract, Request $request,
                                      UserService $userService, TenantService $tenantService, GeneralUtility $generalUtility, LoggerInterface $requestLogger): view
    {
        $curDate = new \DateTime('now');
        try {
            $aObject = $this->validateData($userService, $property, $object, $contract);
            $list = $tenantService->getContractDetail($aObject['contract'], $this->locale, $request, $this->getUser());
            $data = $generalUtility->handleSuccessResponse('detailFetchSuccess', $list);
        } catch (ResourceNotFoundException | EntityNotFoundException | \Exception  $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to delete a contract.
     * # Response
     * ## Success response ##
     *       {
     *           "data": [],
     *           "error": false,
     *           "message": "Contract deleted successfully"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Invalid Contract"
     *       }
     * @Route("/delete/{property}/{object}/{contract}", name="balu_contract_delete", methods={"DELETE"})
     * @Operation(
     *      tags={"Contract"},
     *      summary="API end point to delete a contract"
     *     ),
     * @OA\Response(
     *         response="200",
     *         description="Returned on successful response"
     *     ),
     * @OA\Response(
     *         response="400",
     *         description="Returned when request is not valid"
     *     ),
     * @OA\Response(
     *          response="401",
     *          description="User not authenticated"
     *     ),
     * @OA\Response(
     *         response="500",
     *         description="Internal Error"
     *     )
     *)
     * @param string $property
     * @param string $object
     * @param string $contract
     * @param UserService $userService
     * @param TenantService $tenantService
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @return View
     */
    public function delete(string $property, string $object, string $contract, UserService $userService, TenantService $tenantService,
                           GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request): view
    {
        $curDate = new \DateTime('now');
        try {
            $aObject = $this->validateData($userService, $property, $object, $contract);
            $em = $this->doctrine->getManager();
            $tenantService->deleteContract($aObject['contract']);
            $em->flush();
            $data = $generalUtility->handleSuccessResponse('deleteContractSuccess');
        } catch (ResourceNotFoundException | \Exception  $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     *
     * @param UserService $userService
     * @param string|null $property
     * @param string|null $object
     * @param string|null $contract
     * @return array
     */
    private function validateData(UserService $userService, ?string $property = null, ?string $object = null, ?string $contract = null): array
    {
        $em = $this->doctrine->getManager();
        $user = $this->getUser();
        $oContract = $oObject = $oProperty = null;
        $oProperty = $em->getRepository(Property::class)->findOneBy(['publicId' => $property, 'deleted' => 0]);
        if (!$oProperty instanceof Property) {
            throw new ResourceNotFoundException('invalidProperty');
        }
        $oObject = $em->getRepository(Apartment::class)->findOneBy(['publicId' => $object, 'property' => $oProperty, 'deleted' => 0]);
        if (!$oObject instanceof Apartment) {
            throw new ResourceNotFoundException('objectNotFound');
        }
        if (!is_null($contract)) {
            $oContract = $em->getRepository(ObjectContracts::class)->findOneBy(['publicId' => $contract, 'object' => $oObject, 'deleted' => 0]);
            if (!$oContract instanceof ObjectContracts) {
                throw new ResourceNotFoundException('invalidContract');
            }
        }
        return ['contract' => $oContract, 'object' => $oObject, 'property' => $oProperty];
    }

    /**
     * API end point to terminate a contract.
     * # Request
     * In request body, system expects object details as JSON.
     * ## Example request to terminate a contract
     *       {
     *           "noticeReceiptDate": "1995-12-22",
     *           "terminationDate": "1995-12-22",
     *           "property": ""
     *           "object": ""
     *       }
     * # Response
     * ## Success response ##
     *       {
     *          "data": [],
     *          "error": false,
     *          "message": "Contract terminated successfully"
     *        }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Invalid contract"
     *       }
     * @Route("/terminate/{contract}", name="balu_terminate_contract", methods={"POST"})
     * @Operation(
     *      tags={"Contract"},
     *      summary="API end point to terminate a contract.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="noticeReceiptDate", type="date", default="", example="1995-12-22"),
     *               @OA\Property(property="terminationDate", type="date", default="", example="1995-12-22"),
     *               @OA\Property(property="property", type="date", default="", example=""),
     *               @OA\Property(property="object", type="date", default="", example=""),
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
     * @param string $contract
     * @param Request $request
     * @param UserService $userService
     * @param TenantService $tenantService
     * @param GeneralUtility $generalUtility
     * @return View
     */
    public function terminate(string $contract, Request $request, UserService $userService, TenantService $tenantService,
                              GeneralUtility $generalUtility, LoggerInterface $requestLogger): view
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            $aObject = $this->validateData($userService, $request->get('property'), $request->get('object'), $contract);
            $tenantService->terminateContract($aObject['contract'], $request, $this->getUser());
            $em->flush();
            $em->getConnection()->commit();
            $data = $generalUtility->handleSuccessResponse('terminationSuccess', []);
        } catch (ResourceNotFoundException | \Exception  $e) {
            $em->rollback();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to check notice period violation a contract.
     *
     * # Request
     * In request body, system expects object details as JSON.
     * ## Example request to terminate a contract
     *       {
     *           "noticeReceiptDate": "1995-12-22",
     *           "terminationDate": "1995-12-22",
     *           "property": "uuid"
     *           "object": "uuid"
     *       }
     * # Response
     * ## Success response ##
     *       {
     *          "data": [],
     *          "error": false,
     *          "message": "Contract terminated successfully"
     *        }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Invalid contract"
     *       }
     * @Route("/check/{contract}", name="balu_check_notice", methods={"POST"})
     * @Operation(
     *      tags={"Contract"},
     *      summary="API end point to check violation of notice period a contract.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="noticeReceiptDate", type="date", default="", example="1995-12-22"),
     *               @OA\Property(property="terminationDate", type="date", default="", example="1995-12-22"),
     *               @OA\Property(property="property", type="date", default="", example=""),
     *               @OA\Property(property="object", type="date", default="", example=""),
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
     * @param string $contract
     * @param Request $request
     * @param UserService $userService
     * @param TenantService $tenantService
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function checkNoticePeriod(string $contract, Request $request, UserService $userService, TenantService $tenantService,
                                      GeneralUtility $generalUtility, LoggerInterface $requestLogger): view
    {
        $curDate = new \DateTime('now');
        try {
            $aObject = $this->validateData($userService, $request->get('property'), $request->get('object'), $contract);
            $data = $generalUtility->handleSuccessResponse('terminationSuccess', []);
            if (!$tenantService->checkNoticePeriod($aObject['contract'], $request)) {
                $data = $generalUtility->handleSuccessResponse('noticePeriodFail', [call_user_func_array(array($aObject['contract']->getNoticePeriod(), 'getName' . ucfirst($this->locale)), [])]);
            }
        } catch (ResourceNotFoundException | UnexpectedValueException | \Exception  $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to revoke a contract.
     * # Request
     * In request body, system expects object details as JSON.
     * ## Example request to revoke a contract
     *       {
     *           "property": "uuid"
     *           "object": "uuid"
     *       }
     * # Response
     * ## Success response ##
     *       {
     *          "data": [],
     *          "error": false,
     *          "message": "Contract revoked successfully"
     *        }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Invalid contract"
     *       }
     * @Route("/revoke/{contract}", name="balu_revoke_contract", methods={"POST"})
     * @Operation(
     *      tags={"Contract"},
     *      summary="API end point to revoke a contract.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="property", type="date", default="", example=""),
     *               @OA\Property(property="object", type="date", default="", example=""),
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
     * @param string $contract
     * @param Request $request
     * @param UserService $userService
     * @param TenantService $tenantService
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function revoke(string $contract, Request $request, UserService $userService, TenantService $tenantService,
                           GeneralUtility $generalUtility, LoggerInterface $requestLogger): view
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            $aObject = $this->validateData($userService, $request->get('property'), $request->get('object'), $contract);
            $tenantService->revokeContract($aObject['contract'], $aObject['object']);
            $em->flush();
            $em->getConnection()->commit();
            $data = $generalUtility->handleSuccessResponse('revokeSuccess', []);
        } catch (ResourceNotFoundException | \Exception  $e) {
            $em->rollback();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to start date of contract is valid or not.
     *
     * # Request
     * In request body, system expects object details as JSON.
     * ## Example request to terminate a contract
     *       {
     *           "object": "",
     *           "startDate": "1995-12-22",
     *           "property": ""
     *       }
     * # Response
     * ## Success response ##
     *       {
     *          "data": [],
     *          "error": false,
     *          "message": "Contract terminated successfully"
     *        }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Invalid contract"
     *       }
     * @Route("/check-date", name="balu_check_startdate", methods={"POST"})
     * @Operation(
     *      tags={"Contract"},
     *      summary="API end point to validate start date of contract.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="startDate", type="date", default="", example="1995-12-22"),
     *               @OA\Property(property="property", type="date", default="", example=""),
     *               @OA\Property(property="object", type="date", default="", example=""),
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
     * @param UserService $userService
     * @param TenantService $tenantService
     * @param GeneralUtility $generalUtility
     * @param TranslatorInterface $translator
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function checkStartDate(Request $request, UserService $userService, TenantService $tenantService, GeneralUtility $generalUtility,
                                   TranslatorInterface $translator, LoggerInterface $requestLogger): view
    {
        $curDate = new \DateTime('now');
        try {
            $aObject = $this->validateData($userService, $request->get('property'), $request->get('object'));
            if (!$tenantService->checkStartDate($aObject['object'], $request)) {
                throw new ConflictHttpException($translator->trans('dateConflictError', [], null, $this->locale));
            }
            $data = $generalUtility->handleSuccessResponse('validationSuccess', []);
        } catch (ResourceNotFoundException | ConflictHttpException | \Exception  $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }
}
