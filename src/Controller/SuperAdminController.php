<?php

/**
 * This file is part of the Balu Property package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Form\UserProfileType;
use Nelmio\ApiDocBundle\Annotation\Operation;
use App\Utils\GeneralUtility;
use FOS\RestBundle\View\View;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use App\Service\SuperAdminService;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Psr\Log\LoggerInterface;
use App\Service\PropertyService;
use App\Service\UserService;
use App\Entity\Role;
use App\Entity\Property;
use App\Service\DMSService;
use App\Entity\Payment;
use App\Entity\UserIdentity;
use App\Entity\ResetObject;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

/**
 * AdministratorController
 *
 * Controller to manage administrator related actions.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 * @Route("/super-admin")
 */
final class SuperAdminController extends BaseController
{
    /**
     * API end point to get feedbacks
     *
     * # Response
     * ## Success response ##
     *       {
     *           "data": [
     *               {
     *                   "subject": "latest",
     *                   "message": "tests",
     *                   "sendBy": {
     *                       "uuid": "1ecc51de-292a-63fc-8c7b-0242ac120004",
     *                       "name": "Test User"
     *                   }
     *               },
     *           ],
     *           "error": false,
     *           "message": "dataFetchSuccess"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "formError"
     *       }
     * @Route("/feedback", name="balu_get_feedback", methods={"GET"})
     * @Operation(
     *      tags={"Super Admin"},
     *      summary="API end point to all feedbacks",
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
     *          response="403",
     *          description="User not permitted"
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Internal Error"
     *     )
     *)
     * @param GeneralUtility $generalUtility
     * @param SuperAdminService $superAdminService
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @param UserService $userService
     * @return View
     */
    public function feedBackList(GeneralUtility $generalUtility, SuperAdminService $superAdminService, LoggerInterface $requestLogger, Request $request, UserService $userService): View
    {
        $curDate = new \DateTime('now');
        try {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
            $param['offset'] = $request->get('offset');
            $param['limit'] = $request->get('limit');
            $param['searchKey'] = $request->get('searchKey');
            $data = $generalUtility->handleSuccessResponse('dataFetchSuccess', $superAdminService->getFeedbacks($param));
        } catch (AccessDeniedException | \Exception $e) {
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
     *     summary="API end point to get profile overview.",
     *     @Security(name="Bearer")
     * )
     * @OA\Tag(name="Super Admin")
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param PropertyService $propertyService
     * @param UserService $userService
     * @return View
     * @Route("/property/list", name="balu_admin_list_property", methods={"GET"})
     */
    public function list(Request $request, GeneralUtility $generalUtility, PropertyService $propertyService, UserService $userService): View
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $em = $this->doctrine->getManager();
        $user = $this->getUser();
        $result['data'] = [];
        $param['offset'] = $request->get('offset');
        $param['searchKey'] = $request->get('searchKey');
        $param['limit'] = $request->get('limit');
        $currentUserRole = $userService->getCurrentUserRole($user, $this->currentRole);
        $role = $em->getRepository(Role::class)->findOneBy(['roleKey' => $currentUserRole]);
        // pagination change end
        $results = $em->getRepository(Property::class)->getProperties($user, $param, $role);
        if (!empty($results)) {
            foreach ($results as $propertyResult) {
                $result['data'][] = $propertyService->generatePropertyArray($propertyResult, $request, false, $this->locale, $user);
            }
        }
        $result['count'] = $em->getRepository(Property::class)->countProperties($user, $role);
        if (isset($param['limit']) && $param['limit'] && $result['count']) {
            $result['maxPage'] = (int)ceil($result['count'] / $param['limit']);
        }
        $data = $generalUtility->handleSuccessResponse('propertyListSuccess', $result);

        return $this->response($data);
    }

    /**
     * API end point to list Payment details of currently logged in user.
     *
     * # Request
     *
     * @Route("/payment/list", name="balu_list_payment", methods={"GET"})
     * @Operation(
     *      tags={"Super Admin"},
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
     * @param DMSService $dmsService
     * @return View
     */
    public function paymentList(GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request, DMSService $dmsService): View
    {
        $curDate = new \DateTime('now');
        try {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
            $admin = false;
            $user = $this->getUser();
            $em = $this->doctrine->getManager();
            $currentUserRole = $this->currentRole;
            $role = $dmsService->convertSnakeCaseString($currentUserRole);
            $param['offset'] = $request->get('offset');
            $param['limit'] = $request->get('limit');
            $payments = $em->getRepository(Payment::class)->getListOfPaymentsByLoggedInUser($user, $param, $role, $admin);
            $result['count'] = $em->getRepository(Payment::class)->getCountOfPaymentsByLoggedInUser($user, $role, $admin);
            if ($param['limit'] && $result['count']) {
                $result['maxPage'] = (int)ceil($result['count'] / $param['limit']);
            }
            $data = $generalUtility->handleSuccessResponse('listFetch', $payments);
        } catch (InvalidPasswordException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }
        return $this->response($data);
    }

    /**
     * API end point to get all users
     *
     * ## Success response ##
     *
     *       {
     *           "currentRole": "owner",
     *           "data": {
     *           },
     *           "error": false,
     *           "message": "listFetch"
     *       }
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns all users",
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
     * @OA\Tag(name="Super Admin")
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @return View
     * @Route("/user/list", name="balu_admin_get_users", methods={"GET"})
     */
    public function listAllUsers(Request $request, GeneralUtility $generalUtility, LoggerInterface $requestLogger): view
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $user = $this->getUser();
        $data = $generalUtility->handleFailedResponse('FetchFailed');
        try {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
            if ($user instanceof UserIdentity) {
                $params['offset'] = $request->get('offset');
                $params['limit'] = $request->get('limit');
                $params['sort'] = $request->get('sort') ? $request->get('sort') : 'firstName';
                $params['order'] = $request->get('order') ? $request->get('order') : 'ASC';
                $params['search'] = $request->get('search');
                $params['filter']['role'] = $request->get('role');
                $users = $em->getRepository(UserIdentity::class)->getAllUsers($user, $params, false, $this->locale);
                foreach ($users as $usr) {
                    $result['users'][] = $usr;
                }
                $result['count'] = $em->getRepository(UserIdentity::class)->getAllUsers($user, [], true);
                unset($params['limit']);
                unset($params['offset']);
                $data = $generalUtility->handleSuccessResponse('listFetch', $result);
            }
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to list object reset requests.
     *
     * # Request
     *
     * @Route("/object-reset/list", name="balu_reset_list", methods={"GET"})
     * @Operation(
     *      tags={"Super Admin"},
     *      summary="API end point to list object reset requests",
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
     * @return View
     */
    public function objectResetRequestList(GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request): View
    {
        $curDate = new \DateTime('now');
        try {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
            $user = $this->getUser();
            $result = [];
            if ($user instanceof UserIdentity) {
                $em = $this->doctrine->getManager();
                $param['offset'] = $request->get('offset');
                $param['limit'] = $request->get('limit');
                $result['objects'] = $em->getRepository(ResetObject::class)->getResetList($param);
                $result['count'] = $em->getRepository(ResetObject::class)->getResetList($param, true);
                if ($param['limit'] && $result['count']) {
                    $result['maxPage'] = (int)ceil($result['count'] / $param['limit']);
                }
            }
            $data = $generalUtility->handleSuccessResponse('listFetch', $result);
        } catch (InvalidPasswordException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }
        return $this->response($data);
    }

    /**
     * API end point to get detail data of reset request.
     *
     * # Request
     *
     * @Route("/object-reset-detail/{property}", name="balu_reset_detail", methods={"GET"})
     * @Operation(
     *      tags={"Super Admin"},
     *      summary="API end point to get detail data of reset request.",
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
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @return View
     */
    public function getResetDataByProperty(string $property, GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request): View
    {
        $curDate = new \DateTime('now');
        try {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
            $user = $this->getUser();
            $result = [];
            if ($user instanceof UserIdentity) {
                $em = $this->doctrine->getManager();
                $oProperty = $em->getRepository(Property::class)->findOneBy(['publicId' => $property, 'deleted' => 0]);
                if (!$oProperty instanceof Property) {
                    throw new ResourceNotFoundException('invalidProperty');
                }
                $result['objects'] = $em->getRepository(ResetObject::class)->getResetList([], false, $oProperty);
            }
            $data = $generalUtility->handleSuccessResponse('listFetch', $result);
        } catch (ResourceNotFoundException | InvalidPasswordException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }
        return $this->response($data);
    }

    /**
     * API end point to reset objects.
     *
     * # Request
     *
     * @Route("/reset-objects/{property}", name="balu_reset", methods={"POST"})
     * @Operation(
     *      tags={"Super Admin"},
     *      summary="API end point to reset objects.",
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
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @return View
     */
    public function resetObjects(string $property, GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        try {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
            $user = $this->getUser();
            $result = [];
            $em->beginTransaction();
            if ($user instanceof UserIdentity) {
                $oProperty = $em->getRepository(Property::class)->findOneBy(['publicId' => $property, 'deleted' => 0]);
                if (!$oProperty instanceof Property) {
                    throw new ResourceNotFoundException('invalidProperty');
                }
            }
            $em->flush();
            $em->getConnection()->commit();
            $data = $generalUtility->handleSuccessResponse('listFetch', $result);
        } catch (ResourceNotFoundException | InvalidPasswordException | \Exception $e) {
            $em->rollback();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }
        return $this->response($data);
    }

    /**
     * API end point to update a selected user profile.
     *
     * # Request
     * In request body, system expects user details as JSON.
     * ## gender codes are currently male, female, and trans
     * ## Example request to update user profile
     *      {
     *          "companyName" : "company",
     *          "gender": "Male",
     *          "latitude": "",
     *          "longitude" : "",
     *          "street": "street",
     *          "zipCode": "zip Code",
     *          "houseNumber": "house Number",
     *          "city": "city",
     *          "user": "1ec34f72-e1af-6fde-b32b-df4933035eec"
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
     * @Route("/update-profile", name="balu_admin_update_profile", methods={"POST"})
     * @Operation(
     *      tags={"Super Admin"},
     *      summary="API end point to update a selected user profile.",
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
     *               @OA\Property(property="user", type="string", default="", example="1ec34f72-e1af-6fde-b32b-df4933035eec"),
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
     * @return View
     */
    public function updateProfile(Request $request, GeneralUtility $generalUtility, UserService $userService, LoggerInterface $requestLogger): View
    {
        $em = $this->doctrine->getManager();
        $curDate = new \DateTime('now');
        $user = $em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $request->request->get('user')]);
        if (!$user instanceof UserIdentity) {
            $data = $generalUtility->handleFailedResponse('inValidUser');
            return $this->response($data);
        }
        $request->request->remove('user');
        $form = $this->createNamedForm(UserProfileType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->beginTransaction();
            try {
                $userService->updateUser($form, $user, true);
                $em->flush();
                $em->commit();
                $data = $generalUtility->handleSuccessResponse('userUpdatedSuccessful', $userService->getProfile($user->getPublicId(), $this->locale));
            } catch (CustomUserMessageAccountStatusException | \Exception $e) {
                $em->rollback();
                $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
                $data = $generalUtility->handleFailedResponse('userUpdateFailed');
            }
        } else {
            $data = $generalUtility->handleFailedResponse('formError', 400, $this->getErrorsFromForm($form));
        }
        return $this->response($data);
    }
}