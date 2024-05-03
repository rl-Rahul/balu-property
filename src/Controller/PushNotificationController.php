<?php

/**
 * This file is part of the Balu Property package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\Operation;
use FOS\RestBundle\View\View;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Security;
use App\Service\UserService;
use App\Utils\GeneralUtility;
use App\Utils\ValidationUtility;
use Psr\Log\LoggerInterface;

/**
 * PushNotificationController
 *
 * Controller to manage Push Notifications.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 * @Route("/notification")
 */
final class PushNotificationController extends BaseController
{


    /**
     * API end point to get current user notifications.
     * In request, the api expects count and page
     *
     * The response contains the notification uuid, the message, isRead, event and event uuid and url
     *
     * # Response
     *
     * ## Success response ##
     *       {
     *        "data": [
     *            {
     *            "totalRowCount": 2,
     *            "totalReadCount": 0,
     *            "rows": [
     *                   {
     *                   "identifier": "1ec4b8ae-630d-681a-af52-731ff50ebec7",
     *                   "message": "Your offer has been rejected",
     *                   "isRead": false,
     *                   "event": "OFFER_REJECT",
     *                   "eventId": "1ec4b6a5-03f9-6852-a83c-d7207649ff39",
     *                   "url": ""
     *                   }]
     *               }
     *        ],
     *        "error": false
     *       }
     *
     * ## Failed response ##
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Fetching list failed."
     *      }
     * @Route("/list", name="get_user_notifications", methods={"GET"})
     * @Operation(
     *      tags={"Notification"},
     *      summary="API end point to get user notifications.",
     *      @Security(name="Bearer"),
     *        @OA\Parameter(
     *          name="filter[isRead]",
     *          in="query",
     *          description="Filter by read status",
     *          @OA\Schema(type="integer")
     *      ),
     *        @OA\Parameter(
     *          name="limit",
     *          in="query",
     *          description="Total number of results to be fetched",
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Parameter(
     *          name="offset",
     *          in="query",
     *          description="Page",
     *          @OA\Schema(type="integer")
     *     ),
     *
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
     * @param UserService $userService
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function getUserNotifications(Request $request, GeneralUtility $generalUtility, UserService $userService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        try {
            $data = $generalUtility->handleSuccessResponse('listFetchSuccess', $userService->getUserNotifications($request, $this->currentRole, $this->getUser(), $this->locale));
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse('listFetchFailed');
        }

        return $this->response($data);
    }

    /**
     * API end point to change read status of notification.
     * In request, the api expects notificationId and isRead
     *
     * ## Example request to update user settings
     *      {
     *          "notificationId":["1ec52818-da84-6248-96da-391aae371aa1", "1ec52811-30dd-63f4-9c00-d3ab0f64c587"],
     *          "isRead": true
     *      }
     *
     * # Response
     *
     * ## Success response ##
     *       {
     *        "data": [
     *            {
     *            "totalRowCount": 2,
     *            "totalReadCount": 0,
     *            "rows": [
     *                   {
     *                   "identifier": "1ec4b8ae-630d-681a-af52-731ff50ebec7",
     *                   "message": "Your offer has been rejected",
     *                   "isRead": false,
     *                   "event": "OFFER_REJECT",
     *                   "eventId": "1ec4b6a5-03f9-6852-a83c-d7207649ff39"
     *                   }]
     *               }
     *        ],
     *        "error": false
     *       }
     *
     * ## Failed response ##
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Failed to change status."
     *      }
     * @Route("/read-status", name="balu_read_notification", methods={"PATCH"})
     * @Operation(
     *      tags={"Notification"},
     *      summary="API end point to change read status of notification.",
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *              @OA\Property(property="uuid", type="array", @OA\Items()),
     *              @OA\Property(property="isRead", type="bool")
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
     * @param UserService $userService
     * @param validationUtility $validationUtility
     * @param LoggerInterface $requestLogger
     * @return View
     * @throws \Exception
     */
    public function changeNotificationReadStatus(Request $request, GeneralUtility $generalUtility, UserService $userService, ValidationUtility $validationUtility, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $isRead = $request->request->get('isRead') ? true : false;
        $request->request->set('isRead', $isRead);
        $aViolations = $validationUtility->validateData('notificationStatus', $request->request->all());
        if ($aViolations) {
            $data = $generalUtility->handleFailedResponse('mandatoryFieldsMissing', 400,
                $generalUtility->formatErrors($aViolations));
            return $this->response($data);
        }
        try {
            $userService->changeNotificationReadStatus($request, $this->getUser());
            $data = $generalUtility->handleSuccessResponse('notificationStatusChangeSuccess');
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }
}
