<?php

/**
 * This file is part of the Balu Property package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Utils\GeneralUtility;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\View\View;
use App\Entity\UserIdentity;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use App\Service\PropertyGroupService;
use App\Entity\PropertyGroup;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Utils\ValidationUtility;
use App\Entity\Property;
use Psr\Log\LoggerInterface;

/**
 * PropertyGroupController
 *
 * Controller to manage group related actions.
 *
 * @package         BaluProperty
 * @subpackage      App
 * @author          pitsolutions.ch
 * @Route("/property/group")
 */
final class PropertyGroupController extends BaseController
{
    /**
     * API end point to create property group
     *
     * # Request
     * In request body, system expects JSON.
     * ## Example request to create new group
     *      {
     *          "name": "test"
     *      }
     *
     * ## Success response ##
     *
     *      {
     *           "data": {
     *               "identifier": 8,
     *               "public_id": "1ec8a694-e445-6a2c-8b03-0242ac120004",
     *               "created_at": "2022-02-10T12:02:21+00:00",
     *               "deleted": false,
     *               "name": "test",
     *               "created_by": {
     *                   "identifier": 1,
     *                   "public_id": "1ec79175-91e0-6582-bbe8-0242ac120004",
     *                   "created_at": "2022-01-19T11:02:52+00:00",
     *                   "deleted": false,
     *                   "first_name": "Test",
     *                   "last_name": "User",
     *                   "enabled": true,
     *                   "user": {
     *                       "identifier": 1,
     *                       "public_id": "1ec79175-7f5a-6228-b3d0-0242ac120004",
     *                       "created_at": "2022-01-19T11:02:48+00:00",
     *                       "deleted": false,
     *                       "property": "test@yopmail.com",
     *                       "roles": [
     *                           "ROLE_USER"
     *                       ],
     *                   }
     *               }
     *           },
     *           "error": false,
     *           "message": "New Property Added Successfully."
     *       }
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
     *     summary="API end point to create property grouop.",
     *     @Security(name="Bearer")
     * )
     * @OA\Tag(name="Property Group")
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param PropertyGroupService $propertyGroupService
     * @param ValidationUtility $validationUtility
     * @return View
     * @Route("/create", name="balu_new_group_property", methods={"POST"})
     * @throws \Exception
     */
    public function create(Request $request, GeneralUtility $generalUtility, PropertyGroupService $propertyGroupService, ValidationUtility $validationUtility, LoggerInterface $requestLogger): view
    {
        $curDate = new \DateTime('now');
        $aViolations = $validationUtility->validateData('Group', $request->request->all());
        if (!empty($aViolations) && isset($aViolations['name'])) {
            $data = $generalUtility->handleFailedResponse($aViolations['name'][0], 400,
                $generalUtility->formatErrors($aViolations));
            return $this->response($data);
        }
        try {
            $group = $propertyGroupService->createUpdatePropertyGroup($this->getUser(), $request);
            $data = $generalUtility->handleSuccessResponse('groupCreateSuccess', $group);
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleSuccessResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to edit property group
     *
     * # Request
     * In request body, system expects JSON.
     * ## Example request to edit group
     * # Request
     *      {
     *          "name": "test1"
     *      }
     * ## Success response ##
     *
     *      {
     *           "data": {
     *               "identifier": 8,
     *               "public_id": "1ec8a694-e445-6a2c-8b03-0242ac120004",
     *               "created_at": "2022-02-10T12:02:21+00:00",
     *               "deleted": false,
     *               "name": "test",
     *               "created_by": {
     *                   "identifier": 1,
     *                   "public_id": "1ec79175-91e0-6582-bbe8-0242ac120004",
     *                   "created_at": "2022-01-19T11:02:52+00:00",
     *                   "deleted": false,
     *                   "first_name": "Test",
     *                   "last_name": "User",
     *                   "enabled": true,
     *                   "user": {
     *                       "identifier": 1,
     *                       "public_id": "1ec79175-7f5a-6228-b3d0-0242ac120004",
     *                       "created_at": "2022-01-19T11:02:48+00:00",
     *                       "deleted": false,
     *                       "property": "test@yopmail.com",
     *                       "roles": [
     *                           "ROLE_USER"
     *                       ],
     *                   }
     *               }
     *           },
     *           "error": false,
     *           "message": "Group updated successfully."
     *       }
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
     *     summary="API end point to update user's property group.",
     *     @Security(name="Bearer")
     * )
     * @OA\Tag(name="Property Group")
     * @param Request $request
     * @param string $uuid
     * @param GeneralUtility $generalUtility
     * @param PropertyGroupService $propertyGroupService
     * @param ValidationUtility $validationUtility
     * @return View
     * @throws \Exception
     * @Route("/{uuid}", name="balu_edit_group_property", methods={"PUT"})
     */
    public function edit(Request $request, string $uuid, GeneralUtility $generalUtility, PropertyGroupService $propertyGroupService, ValidationUtility $validationUtility, LoggerInterface $requestLogger): view
    {
        $curDate = new \DateTime('now');
        $aViolations = $validationUtility->validateData('Group', $request->request->all());
        if (!empty($aViolations) && isset($aViolations['name'])) {
            $data = $generalUtility->handleFailedResponse($aViolations['name'][0], 400,
                $generalUtility->formatErrors($aViolations));
            return $this->response($data);
        }
        try {
            $groupObj = $this->validatePermission($this->getUser(), $uuid, $this->currentRole, $this->parameterBag);
            $group = $propertyGroupService->createUpdatePropertyGroup($this->getUser(), $request, $groupObj);
            $data = $generalUtility->handleSuccessResponse('groupUpdateSuccess', $group);
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleSuccessResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to list current user's property group
     *
     * ## Success response ##
     *
     *      {
     *          "data": {
     *               "identifier": 1,
     *               "public_id": "1ec8b0df-3c1f-6d52-b5bf-0242ac120004",
     *               "created_at": "2022-02-11T07:40:57+00:00",
     *               "deleted": false,
     *               "name": "dfdf",
     *               "userId": "1ec8b09d-9e2e-6378-9830-0242ac120004",
     *               "propertyCount": 3
     *           },
     *           "error": false,
     *           "message": "listSuccess."
     *       }
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
     *     summary="API end point to list current user's property group.",
     *     @Security(name="Bearer")
     * )
     * @OA\Tag(name="Property Group")
     * @param GeneralUtility $generalUtility
     * @param PropertyGroupService $groupService
     * @return View
     * @Route("/list", name="balu_list_group_property", methods={"GET"})
     */
    public function list(GeneralUtility $generalUtility, PropertyGroupService $groupService, LoggerInterface $requestLogger, Request $request): View
    {
        $curDate = new \DateTime('now');
        try {
            $data = $generalUtility->handleSuccessResponse('listFetchSuccess', $groupService->getUniqueGroups($this->getUser()));
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleSuccessResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to edit property group
     *
     * Example request to edit a property group
     *
     * # Request
     *   {
     *       "name": "test1"
     *   }
     * ## Success response ##
     *
     *      {
     *           "data": {
     *           },
     *           "error": false,
     *           "message": "Group deleted successfully."
     *       }
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
     *     summary="API end point to create property group.",
     *     @Security(name="Bearer")
     * )
     * @OA\Tag(name="Property Group")
     * @param string $uuid
     * @param GeneralUtility $generalUtility
     * @param PropertyGroupService $propertyGroupService
     * @return View
     * @Route("/delete/{uuid}", name="balu_delete_group_property", methods={"DELETE"})
     */
    public function delete(string $uuid, GeneralUtility $generalUtility, PropertyGroupService $propertyGroupService, LoggerInterface $requestLogger, Request $request): view
    {
        $curDate = new \DateTime('now');
        try {
            $em = $this->doctrine->getManager();
            if (!$groupObj = $em->getRepository(PropertyGroup::class)->findOneBy(['deleted' => false, 'publicId' => $uuid])) {
                throw new AccessDeniedException('notValidGroup');
            }
            $groupObj = $this->validatePermission($this->getUser(), $uuid, $this->currentRole, $this->parameterBag);
            $propertyGroupService->deletePropertyGroup($groupObj);
            $em->flush();
            $data = $generalUtility->handleSuccessResponse('groupDeleteSuccess', $groupObj);
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleSuccessResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     *
     * @param UserIdentity $user
     * @param string $uuid
     * @param string $currentRole
     * @param string $params
     * @return PropertyGroup
     * @throws AccessDeniedException
     */
    public function validatePermission(UserIdentity $user, string $uuid, string $currentRole, $params): PropertyGroup
    {
        $em = $this->doctrine->getManager();
        $groupObj = $em->getRepository(PropertyGroup::class)->findOneBy(['deleted' => 0, 'publicId' => $uuid]);
        if ($groupObj instanceof PropertyGroup) {
            if ($groupObj->getCreatedBy() === $user || $currentRole === $params->get('user_roles')['property_admin']) {
                return $groupObj;
            }
        }

        throw new AccessDeniedException('notValidGroup');
    }
}
