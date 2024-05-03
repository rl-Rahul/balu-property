<?php

namespace App\Service;

use App\Entity\Property;
use App\Entity\PropertyUser;
use App\Entity\UserIdentity;
use App\Entity\Damage;
use Doctrine\ORM\EntityManagerInterface;
use App\Utils\ContainerUtility;
use Google\Service\CloudSearch\UserId;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Apartment;
use App\Utils\Constants;
use App\Utils\GeneralUtility;
use App\Entity\Message;
use App\Service\DamageService;
use App\Service\ObjectService;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use App\Entity\Folder;
use App\Entity\MessageReadUser;
use App\Entity\TemporaryUpload;
use App\Entity\MessageDocument;
use App\Utils\FileUploaderUtility;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Entity\Role;
use App\Exception\FormErrorException;
use App\Entity\MessageType;

/**
 * Class DamageService
 * @package App\Service
 */
class MessageService extends BaseService
{

    /**
     * @var ContainerUtility $containerUtility
     */
    private ContainerUtility $containerUtility;

    /**
     * @var EntityManagerInterface $em
     */
    private EntityManagerInterface $em;

    /**
     * @var UserService $userService
     */
    private UserService $userService;

    /**
     * @var GeneralUtility $generalUtility ;
     */
    private GeneralUtility $generalUtility;

    /**
     * @var DamageService $damageService ;
     */
    private DamageService $damageService;

    /**
     * @var ObjectService $objectService ;
     */
    private ObjectService $objectService;

    /**
     * @var DMSService $dmsService
     */
    private DMSService $dmsService;

    /**
     * @var FileUploaderUtility $fileUploaderUtility
     */
    private FileUploaderUtility $fileUploaderUtility;

    /**
     * @var ParameterBagInterface
     */
    private ParameterBagInterface $parameterBag;

    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @var PushNotificationService
     */
    private PushNotificationService $notificationService;

    /**
     * @var PropertyService
     */
    private PropertyService $propertyService;

    public function __construct(ContainerUtility $containerUtility, UserService $userService,
                                GeneralUtility $generalUtility, DamageService $damageService,
                                ObjectService $objectService, DMSService $dmsService,
                                FileUploaderUtility $fileUploaderUtility, EntityManagerInterface $em,
                                ParameterBagInterface $parameterBag, TranslatorInterface $translator,
                                PropertyService $propertyService, PushNotificationService $notificationService)
    {
        $this->containerUtility = $containerUtility;
        $this->userService = $userService;
        $this->generalUtility = $generalUtility;
        $this->damageService = $damageService;
        $this->em = $em;
        $this->parameterBag = $parameterBag;
        $this->translator = $translator;
        $this->objectService = $objectService;
        $this->dmsService = $dmsService;
        $this->fileUploaderUtility = $fileUploaderUtility;
        $this->propertyService = $propertyService;
        $this->notificationService = $notificationService;
    }

    /**
     * processMessage
     *
     * function to process a Message thread
     *
     * @param Request $request
     * @param Message $message
     * @param UserIdentity $user
     * @param string $currentRole
     * @return void || throws Exception
     * @throws
     */
    public function processMessage(Request $request, Message $message, UserIdentity $user, string $currentRole): void
    {
        $em = $this->em;
        $users = [];
        $requestKeys['type'] = $em->getRepository(MessageType::class)->findOneBy(['typeKey' => $request->request->get('type')]);
        $params = [];

        if ($request->request->get('type') != 'ticket') {
            $selectedApartments = $request->request->get('apartment');
            $result = $this->addApartment($request, $message);
            $messageUsers = $this->objectService->getObjectUsers($selectedApartments, false);
            $property = array_unique($result['property']);
            $apartmentList = array_unique($result['apartment']);
        } else {
            $damage = $em->getRepository(Damage::class)->findOneBy(['identifier' => $request->request->get('ticket')]);
            $messageUsers = $this->damageService->getTicketUsers($damage, false);
            $requestKeys['damage'] = $damage;
            $property = (array)$damage->getApartment()->getProperty()->getIdentifier();
            $apartmentList = (array)$damage->getApartment()->getIdentifier();
            $params = ['damage' => $damage->getIdentifier()];
        }

        $requestKeys['createdByRole'] = $em->getRepository(Role::class)->findOneByRoleKey($currentRole);
        $requestKeys['createdBy'] = $user;
        $requestKeys['updatedAt'] = new \DateTime('now');
        $this->containerUtility->convertRequestKeysToSetters($requestKeys, $message);
        $em->refresh($message);

        foreach ($messageUsers as $messageUser) {
            $users[] = $messageUser->getIdentifier();
        }
//        $peopleList = $em->getRepository(PropertyUser::class)->getPeopleList(reset($property));
        $params += ['property' => $property, 'user' => $users, 'apartment' => $apartmentList];
        $peopleListMessageToSend = $em->getRepository(PropertyUser::class)->getPeopleListMessageToSend($params);
        $this->saveMessagesToUsers($message, $user, $peopleListMessageToSend, $property, $apartmentList, $currentRole);
        $this->processFiles($request, $message, $user);
    }

    /**
     * generateMessageDetails
     *
     * function to generate message details array
     *
     * @param Message $message
     * @param Request $request
     * @param UserIdentity $user
     * @param bool|null $list
     * @param string|null $locale
     * @param string|null $currentRole
     * @return array
     */
    public function generateMessageDetails(Message $message, Request $request, UserIdentity $user, ?bool $list = true, ?string $locale = 'en', ?string $currentRole = null): array
    {
        $messageArray = $aptArray = [];
        if ($message->getType()->getTypeKey() === 'ticket') {
            $title = (null !== $message->getDamage()) ? $this->translator->trans($message->getType()->getTypeKey(), [], null, $locale) . ' #' . sprintf(Constants::DISPLAY_ID_FORMAT, $message->getDamage()->getIdentifier()) . ' - ' . $this->translator->trans('message', [], null, $locale) : null;
        } else {
            $title = $this->translator->trans($message->getType()->getTypeKey(), [], null, $locale);
        }
        $recipientsCount = 0;
        if ($message->getType()->getTypeKey() === 'ticket') {
            $ticketUsers = $this->damageService->getTicketUsers($message->getDamage(), false);
            foreach ($ticketUsers as $user) {
                $messageArray['recipients'][] = ['publicId' => $user->getPublicId()];
            }
            $recipientsCount = count($ticketUsers);
            if (!is_null($currentRole)) {
                $recipientsCount = $this->damageService->getChatOptionAvailability($message->getDamage(), $user, $currentRole, true);
            }
            $messageArray['isPropertyActive'] = $message->getDamage()->getApartment()->getProperty()->getActive();
            $messageArray['isApartmentActive'] = $message->getDamage()->getApartment()->getActive();
        } elseif ($message->getType()->getTypeKey() === 'question') {
            $messageObjectArray = [];
            $objects = $message->getApartments();
            foreach ($objects as $object) {
                if (!$object->getDeleted()) {
                    $messageObjectArray[] = $object->getPublicId();
                }
            }
            $messageUsers = $this->objectService->getObjectUsers($messageObjectArray, false);
            foreach ($messageUsers as $user) {
                $messageArray['recipients'][] = ['publicId' => $user->getPublicId()];
            }
        } else {
            $apt = null;
            foreach ($message->getApartments() as $apt) {
                $aptArray[] = $apt->getPublicId();
            }
            $recipientsCount = count($this->objectService->getObjectUsers($aptArray, false));
            $messageArray['isPropertyActive'] = $apt->getProperty()->getActive();
        }
        $messageArray['title'] = $title;
        $messageArray['subject'] = $message->getSubject();
        $messageArray['message'] = $message->getMessage();
        $messageArray['type'] = $message->getType()->getTypeKey();
        $messageArray['isExpired'] = $user->getIsExpired();
        $messageArray['publicId'] = $message->getPublicId();
        $messageArray['archive'] = $message->getArchive();
        $messageArray['createdAt'] = $message->getCreatedAt();
        $messageArray['recipientsCount'] = $recipientsCount;
        $messageArray['createdBy'] = $this->userService->getFormattedData($message->getCreatedBy());
        $messageArray['createdByRole'] = ($locale == 'de') ? $message->getCreatedByRole()->getNameDe() : $message->getCreatedByRole()->getName();
        $readStatus = $this->em->getRepository(Message::class)->getReadStatus($user, $message->getIdentifier());
        $messageArray['isRead'] = $readStatus;
        foreach ($message->getMessageDocuments() as $doc) {
            if ($doc->getDeleted()) {
                continue;
            }
            $messageArray['documents'][] = $this->getMessageFileInfo($doc, $request->getSchemeAndHttpHost());
        }
        if (!$list && !$readStatus) {
            $this->markAsRead($message, $user, $currentRole);
            $messageArray['isRead'] = true;
        }
        if ($user instanceof UserIdentity) {
            $messageArray['expiryDate'] = $user->getExpiryDate();
        }

        return $messageArray;
    }

    /**
     * @param MessageDocument|null $fileInfo
     * @param string $baseUrl
     * @return array
     */
    public function getMessageFileInfo(?MessageDocument $fileInfo, string $baseUrl): array
    {
        $data = [];
        if ($fileInfo instanceof MessageDocument) {
            $data['publicId'] = $fileInfo->getPublicId();
            $data['originalName'] = $fileInfo->getName();
            $data['displayName'] = $fileInfo->getName();
            $data['filePath'] = $fileInfo->getPath();
            $data['mimeType'] = $fileInfo->getMimeType();
            $data['size'] = $fileInfo->getSize();
            $data['folder'] = $fileInfo->getFolder()->getPublicId();
            $data['path'] = $this->dmsService->getDocumentViewUrl(str_replace($this->parameterBag->get('root_directory') . 'public/', '/', $fileInfo->getPath()), $baseUrl, $fileInfo->getMimeType());
            $data['thumbnails'] = $this->dmsService->getThumbnails(pathinfo($fileInfo->getPath(), PATHINFO_BASENAME), $data['path']);
        }

        return $data;
    }

    /**
     * getFormOptions
     *
     * function to get Form Options
     *
     * @param Request $request
     * @return array
     */
    public function getFormOptions(Request $request): array
    {
        $options['validation_groups'] = [];
        if ($request->request->get('type') === 'ticket') {
            $options['validation_groups'][] = 'damageThread';
        } else {
            $options['validation_groups'][] = 'apartment';
        }

        return $options;
    }

    /**
     * formatMessageRequest
     *
     * function to format message request
     *
     * @param Request $request
     * @param string|null $locale
     */
    public function formatMessageRequest(Request $request, ?string $locale = 'en'): void
    {
        $defaultTicketId = null;
        if ($request->request->get('type') === 'ticket') {
            $request->request->set('apartment', null);
            $damage = (null !== $request->request->get('ticket')) ? $this->damageService->validateAndGetDamageObject($request->request->get('ticket')) : null;
            $request->request->set('ticket', (null !== $damage) ? $damage->getIdentifier() : $defaultTicketId);
            $this->setDefaultMessageText($request, $damage, $locale);
        } else {
            $request->request->set('ticket', null);
            $aptArray = [];
            if (null !== $request->request->get('apartment')) {
                foreach ($request->request->get('apartment') as $value) {
                    $object = $this->em->getRepository(Apartment::class)->findOneBy(['publicId' => $value]);
                    $aptArray[] = (null !== $object) ? $object->getIdentifier() : 0;
                }
            }
            $request->request->set('apartment', $aptArray);
        }

        return;
    }

    /**
     * getMessageList
     *
     * function to get list of messages
     *
     * @param Request $request
     * @param UserIdentity $user
     * @param string $currentRole
     * @param string|null $locale
     * @return array
     */
    public function getMessageList(Request $request, UserIdentity $user, string $currentRole, ?string $locale = 'en'): array
    {
        $param['offset'] = $request->get('offset');
        $param['limit'] = $request->get('limit');
        $param['status'] = null;
        $param['apartment'] = false;
        $param['property'] = false;
        $param['type'] = null;
        $currentRole = $this->snakeToCamelCaseConverter($currentRole);
        if (null !== $request->get('filter')) {
            $param['apartment'] = isset($request->get('filter')['apartment']) ? $this->em->getRepository(Apartment::class)->findOneBy(['deleted' => false, 'publicId' => $request->get('filter')['apartment']]) : null;
            $param['property'] = isset($request->get('filter')['property']) ? $this->em->getRepository(Property::class)->findOneBy(['deleted' => false, 'publicId' => $request->get('filter')['property']]) : null;
            $param['damage'] = isset($request->get('filter')['damage']) ? $request->get('filter')['damage'] : null;
            $param['preferredCompany'] = isset($request->get('filter')['preferredCompany']) ? $request->get('filter')['preferredCompany'] : null;
            $param['status'] = isset($request->get('filter')['status']) ? $request->get('filter')['status'] : null;
        }
        $messages = $this->em->getRepository(Message::class)->getAllMessages($user, $currentRole, $param);
        $return = [];
        foreach ($messages as $key => $message) {
            $return[$key] = $this->generateMessageDetails($message, $request, $user, true, $locale, $currentRole);
            $return[$key]['cancelledOrExpired'] = (($message->getDamage() instanceof Damage) && !is_null($message->getDamage()->getApartment()->getProperty())) ?
                $this->propertyService->checkPropertyCancelledOrExpired($message->getDamage()->getApartment()->getProperty()) : '';
        }

        return $return;
    }

    /**
     * function to get list of messages with minimum details
     *
     * @param UserIdentity $user
     * @param string $currentRole
     * @return array
     */
    public function getMessageListWithMinimumDetails(UserIdentity $user, string $currentRole): array
    {
        $param['type'] = null;
        $param['status'] = 'open';
        $messages = $this->em->getRepository(Message::class)->getAllMessages($user, $currentRole, $param, false, true);
        $return = [];
        foreach ($messages as $key => $message) {
            $return[$key] = $message['publicId'];
        }

        return $return;
    }

    /**
     * setMessageId
     *
     * function to set Message Id
     *
     * @param Request $request
     * @param Message|null $message
     * @return void
     */
    public function validateAndSetMessageId(Request $request, ?Message $message): void
    {
        if (!$message instanceof Message) {
            throw new ResourceNotFoundException("invalidData");
        }
        $request->request->set('messageId', $message->getIdentifier());

        return;
    }

    /**
     * archiveMessage
     *
     * function to archiveMessage
     *
     * @param Request $request
     * @param Message $message
     * @param UserIdentity $user
     * @return array
     */
    public function archiveMessage(Request $request, Message $message, UserIdentity $user): array
    {
        $message->setArchive($request->request->get('archive'));
        $this->em->persist($message);

        return $this->generateMessageDetails($message, $request, $user);
    }

    /**
     * archiveMessage
     *
     * function to archiveMessageResponse
     *
     * @param array|null $data
     * @return array
     */
    public function archiveMessageResponse(?array $data): array
    {
        if (empty($data['error'])) {
            $this->em->flush();
            $this->em->commit();
            $data = $this->generalUtility->handleSuccessResponse('archiveSuccess', $data['success']);
        } else {
            $data = $this->generalUtility->handleFailedResponse('formError', 400, $data['error']);
        }

        return $data;
    }

    /**
     * processImages
     *
     * function to process message files
     *
     * @param Request $request
     * @param Message $message
     * @param UserIdentity $user
     * @return void
     * @throws \Exception
     */
    public function processFiles(Request $request, Message $message, UserIdentity $user): void
    {
        $documents = $request->request->get('documents');
        if (!empty($documents)) {
            if ($message->getDamage() instanceof Damage) {
                $objectFolder = $message->getDamage()->getFolder();
            } else {
                $objectFolder = $message->getApartments()[0]->getProperty()->getFolder();
            }
            $folder = $this->em->getRepository(Folder::class)->findOneBy(['name' => Constants::MESSAGE_DOC_FOLDER, 'parent' => $objectFolder]);
            if (is_null($folder)) {
                $this->dmsService->createFolder(Constants::MESSAGE_DOC_FOLDER, $user, true, $objectFolder->getPublicId(), false, true);
                $folder = $this->em->getRepository(Folder::class)->findOneBy(['name' => Constants::MESSAGE_DOC_FOLDER, 'parent' => $objectFolder]);
            }
            if ($message->getFolder() === null) {
                $newFolder = $this->dmsService->createFolder($message->getId(), $user, true, $folder->getPublicId(), false);
                $destinationFolder = $this->em->getRepository(Folder::class)->findOneBy(['publicId' => $newFolder[0]['publicId']]);
                $message->setFolder($destinationFolder);
                $this->em->persist($message);
            } else {
                $destinationFolder = $message->getFolder();
            }
            foreach ($documents as $doc) {
                $tempImageDetail = $this->em->getRepository(TemporaryUpload::class)->findOneBy(['publicId' => $doc]);
                if (null !== $tempImageDetail) {
                    $destinationPath = $destinationFolder->getPath() . '/' . $tempImageDetail->getLocalFileName();
                    $file = new MessageDocument();
                    $this->containerUtility->convertRequestKeysToSetters([
                        'message' => $message,
                        'name' => $tempImageDetail->getLocalFileName(),
                        'path' => $destinationPath,
                        'mimeType' => $tempImageDetail->getMimeType(),
                        'size' => $tempImageDetail->getFileSize(),
                        'displayName' => $tempImageDetail->getOriginalFileName(),
                        'folder' => $folder
                    ], $file);
                    rename($tempImageDetail->getTemporaryUploadPath(), $destinationPath);
                    $this->em->remove($tempImageDetail);
                    $this->em->persist($file);
                    $this->fileUploaderUtility->optimizeFile($file->getPath(), $file->getMimeType());
                }
            }
            $this->em->flush();
        }
    }

    /**
     * markAsRead
     *
     * function to mark Message as read
     *
     * @param Message $message
     * @param UserIdentity $user
     * @param string|null $currentRole
     * @return void
     */
    public function markAsRead(Message $message, UserIdentity $user, ?string $currentRole = null): void
    {
        $fetchParam = ['message' => $message->getIdentifier(), 'user' => $user->getIdentifier(), 'isRead' => false];
        if (!is_null($currentRole)) {
            $currentRole = $this->em->getRepository(Role::class)->findOneBy(['roleKey' => $currentRole]);
            if ($currentRole instanceof Role) {
                $fetchParam += ['role' => $currentRole->getIdentifier()];
            }
        }
        $readUsers = $this->em->getRepository(MessageReadUser::class)->findBy($fetchParam);
        if (!empty($readUsers)) {
            foreach ($readUsers as $messageUser) {
                if ($messageUser instanceof MessageReadUser) {
                    $messageUser->setIsRead(true);
                }
            }
        }

        $this->em->flush();
    }

    /**
     * get DamageList
     *
     * function to get list of damage tickets filtered using free text
     *
     * @param Request $request
     * @param UserIdentity $user
     * @param $currentRole
     * @return array
     * @throws FormErrorException
     */
    public function getFilteredMessageList(Request $request, UserIdentity $user, string $currentRole): array
    {
        if ($request->request->has('text') && $request->request->get('text') != '' &&
            strlen(trim($request->request->get('text'))) < Constants::MIN_SEARCH_CHARACHERS) {
            throw new FormErrorException('minimumCharactersRequiredForSearch');
        }
        $return = [];
        $param['offset'] = $request->get('offset');
        $param['limit'] = $request->get('limit');
        $param['apartment'] = false;
        $param['property'] = false;
        $param['type'] = $request->get('type') !== '' ?
            $this->em->getRepository(MessageType::class)->findOneBy(['typeKey' => $request->get('type')]) : null;
        $param['status'] = $request->get('status') != '' ? $request->get('status') : null;
        $param['text'] = $request->get('text') != '' ? $request->get('text') : null;
        $messages = $this->em->getRepository(Message::class)->getAllMessages($user, $currentRole, $param);
        foreach ($messages as $message) {
            $return[] = $this->generateMessageDetails($message, $request, $user);
        }

        return $return;
    }

    /**
     * setDefaultMessageText
     *
     * function to set default Message Text
     *
     * @param Request $request
     * @param Damage $damage
     * @param string|null $locale
     * @return void
     */
    private function setDefaultMessageText(Request $request, Damage $damage, ?string $locale): void
    {
        //(null === $request->request->get('message')) ? $request->request->set('message', str_replace('%REPLACE%', $this->translator->trans('ticket', [], null, $locale) . ' #' . sprintf(Constants::DISPLAY_ID_FORMAT, $damage->getIdentifier()), $this->translator->trans('defaultMessageText', [], null, $locale))) : null;
        (null === $request->request->get('subject')) ? $request->request->set('subject', $this->translator->trans('ticket', [], null, $locale) . ' #' . sprintf(Constants::DISPLAY_ID_FORMAT, $damage->getIdentifier()) . ' - ' . $this->translator->trans('message', [], null, $locale)) : null;

        return;
    }

    /**
     * removeReadStatus
     *
     * function to remove read status of messages
     *
     * @param Message $message
     * @return void
     */
    public function removeReadStatus(Message $message): void
    {
        $readUsers = $message->getMessageReadUsers();
        if (!empty($readUsers)) {
            foreach ($readUsers as $readUser) {
                if ($readUser instanceof MessageReadUser) {
                    $readUser->setIsRead(false);
                }
            }
            $this->em->flush();
        }
    }

    /**
     * saveMessage
     *
     * function to save messages on message delivery
     *
     * @param Message $message
     * @param UserIdentity $user
     * @param Role|null $role
     * @return void
     */
    public function saveMessage(Message $message, UserIdentity $user, ?Role $role = null)
    {
        $messageReadUser = new MessageReadUser();
        $messageReadUser->setMessage($message);
        $messageReadUser->setUser($user);
        $messageReadUser->setIsRead(false);
        if ($role instanceof Role) {
            $messageReadUser->setRole($role);
        }
        $this->em->persist($messageReadUser);
        $this->em->flush();
    }

    /**
     * addApartment
     *
     * function to add apartment in messages
     *
     * @param Request $request
     * @param Message $message
     * @return array
     */
    private function addApartment(Request $request, Message $message): array
    {
        $em = $this->em;
        $result = [];
        $selectedApartments = $request->request->get('apartment', []);
        foreach ($selectedApartments as $apartmentIdentifier) {
            $apartment = $em->getRepository(Apartment::class)->findOneBy(['identifier' => $apartmentIdentifier]);
            $message->addApartment($apartment);
            $result['apartment'][] = $apartment->getIdentifier();
            $result['property'][] = $apartment->getProperty()->getIdentifier();
        }

        return $result;
    }

    /**
     * saveMessagesToUsers
     *
     * function to add apartment in messages
     *
     * @param Message $message
     * @param UserIdentity $user
     * @param array $peopleListMessageToSend
     * @param array $properties
     * @param array $apartments
     * @param string|null $currentRole
     * @return void
     */
    private function saveMessagesToUsers(
        Message $message,
        UserIdentity $user,
        array $peopleListMessageToSend,
        array $properties,
        array $apartments,
        ?string $currentRole = null
    ): void
    {
        $em = $this->em;
        $responsibleUserList = [];
        $propertyAdminRole = $this->camelCaseConverter(Constants::PROPERTY_ADMIN_ROLE);

        foreach ($peopleListMessageToSend as $list) {
            $responsibleUserList[Constants::OWNER_ROLE] = $list['owner'];
            $responsibleUserList[Constants::JANITOR_ROLE] = $list['janitor'];
            $responsibleUserList[$propertyAdminRole] = $list['administrator'];
            if (isset($list['company']) && !is_null($list['company'])) {
                $responsibleUserList[Constants::COMPANY_ROLE] = $list['company'];
            }

            $params = ['user' => $list['identifier'], 'property' => $properties, 'apartment' => $apartments];
            $roles = $em->getRepository(PropertyUser::class)->getPeopleRoleInProperty($params);

            foreach ($roles as $role) {
                $userObj = $em->getRepository(UserIdentity::class)->findOneBy(['identifier' => $role['user']]);
                $roleObj = $em->getRepository(Role::class)->findOneBy(['roleKey' => $role['roleKey']]);
                $this->saveMessage($message, $userObj, $roleObj);
            }
        }

        foreach ($responsibleUserList as $key => $responsibleUserId) {
            if ($responsibleUserId !== null) {
                $userObj = $em->getRepository(UserIdentity::class)->findOneBy(['identifier' => $responsibleUserId]);
                $roleObj = $em->getRepository(Role::class)->findOneBy(['roleKey' => $key]);
                $this->saveMessage($message, $userObj, $roleObj);
            }
        }
    }
}
