<?php

namespace App\Controller;

use App\Entity\Damage;
use App\Entity\MessageType as MessageTypeEntity;
use App\Entity\PushNotification;
use App\Service\PushNotificationService;
use App\Service\UserService;
use App\Utils\GeneralUtility;
use Doctrine\ORM\EntityNotFoundException;
use FOS\RestBundle\View\View;
use App\Form\MessageType;
use App\Form\MessageArchiveType;
use App\Entity\Message;
use App\Service\MessageService;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Psr\Log\LoggerInterface;
use function PHPUnit\Framework\throwException;

/**
 * MessageController
 *
 * Controller to manage message actions
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 * @Route("/message")
 */
final class MessageController extends BaseController
{
    /**
     * API end point to create a message
     *
     * # Request
     *
     * For adding ticket message/discussion to existing ticket
     *
     *       {
     *          "type":"ticket",
     *          "message": "this is an example.",
     *          "subject": "subject example",
     *          "ticket":  "1ecfb854-a0cd-6078-9e20-0242ac130003" ,
     *          "documents": ["1ecfb854-a0cd-6078-2e23-0242ac130003","1ecfb854-a0cd-6078-1t1d-0242ac130003"]
     *       }
     *
     * For adding question/discussion to an object
     *
     *       {
     *          "type":"question",
     *          "message": "this is an example.",
     *          "subject": "subject example",
     *          "apartment":  ["1ecfb854-a0cd-6078-9e20-0242ac130003"],
     *          "documents": ["1ecfb854-a0cd-6078-2e23-0242ac130003","1ecfb854-a0cd-6078-1t1d-0242ac130003"]
     *       }
     *
     * For adding information to an object
     *
     *       {
     *          "type":"information",
     *          "message": "this is an example.",
     *          "subject": "subject example",
     *          "apartment":   ["1ecfb854-a0cd-6078-9e20-0242ac130003"],
     *          "documents": ["1ecfb854-a0cd-6078-2e23-0242ac130003","1ecfb854-a0cd-6078-1t1d-0242ac130003"]
     *       }
     *
     * ## Success response ##
     *
     *       {
     *           "currentRole": "propertyAdmin",
     *           "data": {
     *               "publicId": "1ed00e3d-e795-68fe-8b96-0242ac130003",
     *               "message": "this is an example.",
     *               "subject": "subject example",
     *               "isRead": false,
     *               "createdAt": "2022-07-11 06:37"
     *           },
     *           "error": false,
     *           "message": "messageSuccess"
     *       }
     *
     * @Operation(
     *      tags={"Message"},
     *      summary="API end point to add a message",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="type", type="string", default="", example=""),
     *               @OA\Property(property="message", type="string", default="", example=""),
     *               @OA\Property(property="subject", type="string", default="", example=""),
     *               @OA\Property(property="apartment", type="string", default="false", example=""),
     *               @OA\Property(property="ticket", type="string", default="", example=""),
     *               @OA\Property(
     *                      property="documents",
     *                      type="array",
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003")
     *               ),
     *          )
     *       )
     *     ),
     *  @OA\Response(
     *     response=200,
     *     description="Returns success message after creating message",
     *  ),
     *  @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     *  ),
     *  @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     *  ),
     *  @OA\Tag(name="Messages")
     * )
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param MessageService $messageService
     * @param LoggerInterface $requestLogger
     * @param UserService $userService
     * @param PushNotificationService $notificationService
     * @return View
     * @Route("/add", name="balu_message_create", methods={"POST"})
     */
    public function create(
        Request $request,
        GeneralUtility $generalUtility,
        MessageService $messageService,
        LoggerInterface $requestLogger,
        UserService $userService,
        PushNotificationService $notificationService): View
    {
        $em = $this->doctrine->getManager();
        $message = new Message();
        if (is_null($request->get('subject')) && $request->get('type') == 'ticket' && !empty($request->get('ticket'))) {
            $damage = $em->getRepository(Damage::class)->findOneBy(['publicId' => $request->get('ticket'), 'deleted' => false]);
            if ($damage instanceof Damage) {
                $messageType = $em->getRepository(MessageTypeEntity::class)->findOneBy(['typeKey' => $request->get('type')]);
                $messageObj = $em->getRepository(Message::class)->findOneBy(['damage' => $damage, 'deleted' => false, 'type' => $messageType]);
                $message = $messageObj instanceof Message ? $messageObj : new Message();
            }
        }
        $em->beginTransaction();
        $curDate = new \DateTime('now');
        try {
            $messageService->formatMessageRequest($request, $this->locale);
            $form = $this->createNamedForm(MessageType::class, $message, $messageService->getFormOptions($request));
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $messageService->processMessage($request, $message, $this->getUser(), $this->currentRole);
                $em->flush();
                $content = [
                    'message' => $request->request->get('message'),
                    'title' => $request->request->has('subject') ? $request->request->get('subject') : null,
                ];
                $userDeviceList = $userService->getUsersDeviceListForSendingMessages($message);
                foreach ($userDeviceList as $key => $item) {
                    if ($key !== $this->getUser()->getIdentifier()) {
                        $content['userRole'] = $item['roles'];
                        $notificationService->sendPushNotification($content, $item['deviceList']);
                    }
                }
                $em->commit();
                $data = $generalUtility->handleSuccessResponse('messageSuccess', $messageService->generateMessageDetails($message, $request, $this->getUser()));
            } else {
                $data = $generalUtility->handleFailedResponse('formError', 400, $this->getErrorsFromForm($form));
            }
        } catch (InvalidArgumentException | UnsupportedUserException | AccessDeniedException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $em->rollBack();
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to get message list.
     *
     * # Request
     * System expects filters as a path parameter.
     *
     * # Response
     * ## Success response ##
     *      {
     *         "currentRole": "propertyAdmin",
     *         "data": [
     *            {
     *                "title": "Ticket #423 - Message",
     *                "type": "ticket",
     *                "publicId": "1ed034d8-7c7d-69e2-b7f9-0242ac130003",
     *                "archive": false,
     *                "createdAt": "2022-07-14T08:18:24+00:00",
     *                "recipientsCount": 4
     *                "isRead": false
     *            },
     *            {
     *                "title": "Information",
     *                "type": "information",
     *                "publicId": "1ed034d6-c700-6be2-9d09-0242ac130003",
     *                "archive": false,
     *                "createdAt": "2022-07-14T08:17:38+00:00",
     *                "recipientsCount": 4
     *                "isRead": false
     *            },
     *            {
     *                "title": "Question",
     *                "type": "question",
     *                "publicId": "1ed034c0-4fe1-6d6a-86c7-0242ac130003",
     *                "archive": false,
     *                "createdAt": "2022-07-14T08:07:34+00:00",
     *                "recipientsCount": 14,
     *                "isRead": false
     *            }
     *         ],
     *         "error": false,
     *         "message": "listSuccess"
     *      }
     * ## Failed response ##
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Fetching Message list failed."
     *      }
     * @Route("/list", name="balu_message_list", methods={"GET"})
     * @Operation(
     *      tags={"Message"},
     *      summary="API end point to get message list.",
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
     *     @OA\Parameter(
     *      name="filter[status]",
     *      in="query",
     *      description="Status can be open/archive or any other damage status",
     *      @OA\Schema(type="string")
     *     ),
     *      @OA\Parameter(
     *      name="filter[damage]",
     *      in="query",
     *      @OA\Schema(type="string")
     *     ),
     *      @OA\Parameter(
     *      name="filter[apartment]",
     *      in="query",
     *      @OA\Schema(type="string")
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
     *
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param MessageService $messageService
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function list(Request $request, GeneralUtility $generalUtility, MessageService $messageService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        try {
            $data = $generalUtility->handleSuccessResponse('listSuccess', $messageService->getMessageList($request, $this->getUser(), $this->currentRole, $this->locale));
        } catch (ResourceNotFoundException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to get message list with limited details.
     *
     * # Request
     * System expects filters as a path parameter.
     *
     * # Response
     * ## Success response ##
     *      {
     *         "currentRole": "propertyAdmin",
     *         "data": [
     *                "1ed034d8-7c7d-69e2-b7f9-0242ac130003",
     *                "1ed034d8-7c7d-69e2-b7f9-0242ac130003"
     *         ],
     *         "error": false,
     *         "message": "listSuccess"
     *      }
     * ## Failed response ##
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Fetching Message list failed."
     *      }
     * @Route("/list-light", name="balu_message_list_light", methods={"GET"})
     * @Operation(
     *      tags={"Message"},
     *      summary="API end point to get message list with limited details.",
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
     * )
     *
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param MessageService $messageService
     * @return View
     * @throws \Exception
     */
    public function listLight(Request $request, GeneralUtility $generalUtility, MessageService $messageService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        try {
            $data = $generalUtility->handleSuccessResponse('listSuccess', $messageService->getMessageListWithMinimumDetails($this->getUser(), $this->currentRole, $this->locale));
        } catch (ResourceNotFoundException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to change archive status of message.
     * In request, the api expects messageId and archive
     *
     * ## Example request
     *      {
     *          "messageId":["1ec52818-da84-6248-96da-391aae371aa1", "1ec52811-30dd-63f4-9c00-d3ab0f64c587"],
     *          "archive": true
     *      }
     *
     * # Response
     *
     * ## Success response ##
     *
     *       {
     *           "currentRole": "propertyAdmin",
     *           "data": {
     *               "publicId": "1ed00e3d-e795-68fe-8b96-0242ac130003",
     *               "archive": true,
     *               "createdAt": "2022-07-11 06:37"
     *           },
     *           "error": false,
     *           "message": "archiveSuccess"
     *       }
     *
     * ## Failed response ##
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "archiveFailed."
     *      }
     * @Route("/archive", name="balu_archive_message", methods={"PATCH"})
     * @Operation(
     *      tags={"Message"},
     *      summary="API end point to archive message",
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *              @OA\Property(property="messageId", type="array", @OA\Items()),
     *              @OA\Property(property="archive", type="bool")
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
     * @param MessageService $messageService
     * @return View
     * @throws \Exception | ResourceNotFoundException
     */
    public function archiveMessage(Request $request, GeneralUtility $generalUtility, MessageService $messageService, LoggerInterface $requestLogger): View
    {
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        $response = [];
        $curDate = new \DateTime('now');
        try {
            $messages = $request->request->get('messageId');
            foreach ($messages as $message) {
                $message = $em->getRepository(Message::class)->findOneBy(['publicId' => $message, 'deleted' => 0]);
                $messageService->validateAndSetMessageId($request, $message);
                $form = $this->createNamedForm(MessageArchiveType::class, $message);
                $form->submit($request->request->all());
                ($form->isSubmitted() && $form->isValid()) ? $response['success'][] = $messageService->archiveMessage($request, $message, $this->getUser()) : $response['error'][] = $this->getErrorsFromForm($form);
            }
            $data = $messageService->archiveMessageResponse($response);
        } catch (\Exception | ResourceNotFoundException $e) {
            $em->rollBack();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to get details of a message
     *
     *
     * # Request
     *
     *
     * ## Success response ##
     *
     *      {
     *          "currentRole": "property_admin",
     *          "data": {
     *              "title": "Ticket #442 - Message",
     *              "subject": "example  subject",
     *              "message": "example message",
     *              "type": "ticket",
     *              "publicId": "1ed0677d-ed14-637c-ab00-0242ac130003",
     *              "archive": false,
     *              "createdAt": "2022-07-18T08:59:02+00:00",
     *              "recipientsCount": 4,
     *              "documents": [
     *                 "1ed0677d-f8a8-6198-a8d5-0242ac130003"
     *              ]
     *          },
     *          "error": false,
     *          "message": "success"
     *      }
     *
     * @Operation(
     *      tags={"Message"},
     *      summary="API end point get details of a message",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="message", type="string", default="", example="1ecdb203-7397-6490-b46f-0242ac1b0004"),
     *          )
     *       )
     *     ),
     *  @OA\Response(
     *     response=200,
     *     description="Returns Message details",
     *  ),
     *  @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     *  ),
     *  @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     *  ),
     *  @OA\Tag(name="Message")
     * )
     * @param Request $request
     * @param string $messageId
     * @param GeneralUtility $generalUtility
     * @param MessageService $messageService
     * @param LoggerInterface $requestLogger
     * @return View
     * @Route("/details/{messageId}", name="balu_message_details", methods={"GET"})
     */
    public function details(Request $request, string $messageId, GeneralUtility $generalUtility, MessageService $messageService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        try {
            $message = $this->doctrine->getManager()->getRepository(Message::class)->findOneByPublicId($messageId);
            $data = $generalUtility->handleSuccessResponse('success', $messageService->generateMessageDetails($message, $request, $this->getUser(), false, $this->locale));
        } catch (ResourceNotFoundException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }


    /**
     * API end point to get a filtered message list using free text search.
     *
     * # Request
     * System expects filters as a path parameter.
     *
     * # Response
     * ## Success response ##
     *      {
     *         "currentRole": "propertyAdmin",
     *         "data": [
     *            {
     *                "title": "Ticket #423 - Message",
     *                "type": "ticket",
     *                "publicId": "1ed034d8-7c7d-69e2-b7f9-0242ac130003",
     *                "archive": false,
     *                "createdAt": "2022-07-14T08:18:24+00:00",
     *                "recipientsCount": 4
     *                "isRead": false
     *            },
     *            {
     *                "title": "Information",
     *                "type": "information",
     *                "publicId": "1ed034d6-c700-6be2-9d09-0242ac130003",
     *                "archive": false,
     *                "createdAt": "2022-07-14T08:17:38+00:00",
     *                "recipientsCount": 4
     *                "isRead": false
     *            },
     *            {
     *                "title": "Question",
     *                "type": "question",
     *                "publicId": "1ed034c0-4fe1-6d6a-86c7-0242ac130003",
     *                "archive": false,
     *                "createdAt": "2022-07-14T08:07:34+00:00",
     *                "recipientsCount": 14,
     *                "isRead": false
     *            }
     *         ],
     *         "error": false,
     *         "message": "listSuccess"
     *      }
     * ## Failed response ##
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Fetching Message list failed."
     *      }
     * @Route("/search", name="balu_message_search", methods={"POST"})
     * @Operation(
     *      tags={"Message"},
     *      summary="API end point to get message list.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="limit", type="integer", default="", example="10"),
     *               @OA\Property(property="offset", type="integer", default="", example="1"),
     *               @OA\Property(property="type", type="string", default="", example="ticket"),
     *               @OA\Property(property="status", type="string", default="", example="open"),
     *               @OA\Property(property="text", type="string", default="", example="text"),
     *          )
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
     *
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param MessageService $messageService
     * @return View
     * @throws \Exception
     */
    public function searchList(Request $request, GeneralUtility $generalUtility, MessageService $messageService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        try {
            $data = $generalUtility->handleSuccessResponse('listSuccess', $messageService->getFilteredMessageList($request, $this->getUser(), $this->currentRole));
        } catch (ResourceNotFoundException | \Exception $e) {
            $data = $generalUtility->handleFailedResponse($e->getMessage());
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to update message updated at
     *
     * # Request
     * System expects filters as a path parameter.
     * /api/2.0/message/update-message
     *
     * # Response
     * ## Success response ##
     *      {
     *         "currentRole": "propertyAdmin",
     *         "data": [
     *            {
     *                "title": "Ticket #423 - Message",
     *                "type": "ticket",
     *                "publicId": "1ed034d8-7c7d-69e2-b7f9-0242ac130003",
     *                "archive": false,
     *                "createdAt": "2022-07-14T08:18:24+00:00",
     *                "recipientsCount": 4
     *                "isRead": false
     *            }
     *         ],
     *         "error": false,
     *         "message": "listSuccess"
     *      }
     * ## Failed response ##
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Fetching Message list failed."
     *      }
     * @Route("/update-message", name="balu_message_update_message", methods={"PATCH"})
     * @Operation(
     *      tags={"Message"},
     *      summary="API end point to update message updated at",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="messageId", type="string", default="", example="1eefaf7c-fa07-6f0a-98d1-5254a2026859"),
     *               @OA\Property(property="content", type="string", default="", example="Test"),
     *               @OA\Property(property="title", type="string", default="", example="Test")
     *          )
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
     *
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param PushNotificationService $notificationService
     * @param UserService $userService
     * @param MessageService $messageService
     * @return View
     */
    public function update(Request $request, GeneralUtility $generalUtility, LoggerInterface $requestLogger,
                           PushNotificationService $notificationService, UserService $userService, MessageService $messageService): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            if ($request->request->has('messageId')) {
                $message = $em->getRepository(Message::class)->findOneBy(['publicId' => $request->request->get('messageId')]);
                if (!$message instanceof Message) {
                    throw new EntityNotFoundException('invalidMessageId');
                }
                $messageService->removeReadStatus($message);
                $content = [
                    'message' => $request->request->get('content'),
                    'title' => $request->request->has('title') ? $request->request->get('title') : null,
                    'messageId' => $request->request->get('messageId')
                ];
                $userDeviceList = $userService->getUsersDeviceListForSendingMessages($message);
                foreach ($userDeviceList as $key => $item) {
                    if ($key !== $this->getUser()->getIdentifier()) {
                        $content['userRole'] = $item['roles'];
                        $notificationService->sendPushNotification($content, $item['deviceList']);
                    }
                }
                $em->flush();
                $em->commit();
            } else {
                throw new EntityNotFoundException('invalidMessageId');
            }
            $data = $generalUtility->handleSuccessResponse('messageUpdatedSuccessfully');
        } catch (ResourceNotFoundException | \Exception $e) {
            $em->rollback();
            $data = $generalUtility->handleFailedResponse($e->getMessage());
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
        }

        return $this->response($data);
    }
}
