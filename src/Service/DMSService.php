<?php

namespace App\Service;

use App\Entity\Document;
use App\Entity\Interfaces\ReturnableDocumentInterface;
use App\Entity\PropertyUser;
use App\Entity\Role;
use App\Entity\TemporaryUpload;
use App\Entity\UserIdentity;
use App\Entity\Property;
use App\Entity\Folder;
use App\Entity\Apartment;
use App\Utils\ValidationUtility;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use App\Utils\Constants;
use App\Utils\ContainerUtility;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Exception\InvalidArgumentException;
use App\Entity\ObjectContracts;
use App\Entity\MessageDocument;
use App\Entity\DamageImage;
use App\Utils\FileUploaderUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

use Symfony\Component\Routing\RouterInterface;
use FOS\RestBundle\Request\ParameterBag;

/**
 * Class DMSService
 * @package App\Service
 */
class DMSService extends BaseService
{
    /**
     * @var ManagerRegistry $doctrine
     */
    private ManagerRegistry $doctrine;

    /**
     * @var ValidationUtility $validationUtility
     */
    private ValidationUtility $validationUtility;

    /**
     * @var ParameterBagInterface $params
     */
    private ParameterBagInterface $params;

    /**
     * @var ContainerUtility $containerUtility
     */
    private ContainerUtility $containerUtility;

    /**
     * @var RequestStack $request
     */
    private RequestStack $request;

    /**
     * @var FileUploaderUtility $fileUploaderUtility
     */
    private FileUploaderUtility $fileUploaderUtility;

    /**
     * @var ContainerInterface $container
     */
    private ContainerInterface $container;

    private $router;

    private $parameterBag;

    /**
     *
     * @param ManagerRegistry $doctrine
     * @param ValidationUtility $validationUtility
     * @param ParameterBagInterface $params
     * @param ContainerUtility $containerUtility
     * @param RequestStack $request
     * @param FileUploaderUtility $fileUploaderUtility
     * @param ContainerInterface $container
     */
    public function __construct(ManagerRegistry $doctrine, ValidationUtility $validationUtility,
                                ParameterBagInterface $params, ContainerUtility $containerUtility, RequestStack $request,
                                FileUploaderUtility $fileUploaderUtility, RouterInterface $router, ParameterBagInterface $parameterBag)
    {
        $this->doctrine = $doctrine;
        $this->validationUtility = $validationUtility;
        $this->params = $params;
        $this->containerUtility = $containerUtility;
        $this->request = $request;
        $this->fileUploaderUtility = $fileUploaderUtility;
        $this->router = $router;
        $this->parameterBag = $parameterBag;
    }

    /**
     * Creates folder
     *
     * @param string $folderName
     * @param UserIdentity $user
     * @param bool $isSystemGenerated
     * @param string|null $parent
     * @param bool $accessibility
     * @param bool|null $forceName
     * @param Role|null $createdByRole
     * @return array
     * @throws \Exception
     */
    public function createFolder(string $folderName, UserIdentity $user, bool $isSystemGenerated = false,
                                 ?string $parent = null, bool $accessibility = false, ?bool $forceName = false, ?Role $createdByRole = null): array
    {
        $folderDisplayName = $folderName;
        $em = $this->doctrine->getManager();
//        $isFolderNameExists = $em->getRepository(Folder::class)->checkIsFolderNameExists($folderDisplayName, $user);
//        if (!is_null($isFolderNameExists)) {
//            throw new InvalidArgumentException('folderNameExists', 400);
//        }
        $fileLocalPath = (!$createdByRole instanceof Role) ? 'file.root_path' : 'company_logo_path';
        $rootFolder = $this->params->get('root_directory') . $this->params->get($fileLocalPath);
        if (!is_null($parent)) {
            $parent = $em->getRepository(Folder::class)->findOneBy(['publicId' => $parent]);
            $rootFolder = $parent->getPath() . '/';
        }
        $fs = new Filesystem();
        try {
            if (!$forceName) {
                $folderName = 'folder' . '-' . uniqid(strtotime('now'));
            }
            if ($isSystemGenerated) {
                $folderDisplayName = $folderDisplayName;
            }
            $folderDisplayName = ucfirst($folderDisplayName);
            $path = $rootFolder . $folderName;
//                $key = 1;
//                while ($fs->exists($path)) {
//                    $folderDisplayName = (string) $folderDisplayName . ' ' . $key ;
//                    $key++;
//                }
            if (!$fs->exists($path)) {
                $fs->mkdir($path . '/', 0755);
            }
            $folder = $this->saveFolderInfo($folderName, $folderDisplayName, $user, $path, $accessibility, $parent, $isSystemGenerated, $createdByRole);
        } catch (IOExceptionInterface $e) {
            throw new \Exception('errorCreatingFolder');
        }

        return $em->getRepository(Folder::class)->getFolderInfo($folder);
    }

    /**
     * Saves documents to respective entities
     *
     * @param string $publicId
     * @return Folder|null
     */
    public function getFolder(string $publicId): ?Folder
    {
        $em = $this->doctrine->getManager();
        return $em->getRepository(Folder::class)->findOneBy(['publicId' => $publicId]);
    }

    /**
     * Saves documents to respective entities
     *
     * @param Folder $folder
     * @return Property|null
     */
    public function getProperty(Folder $folder): ?Property
    {
        $em = $this->doctrine->getManager();
        return $em->getRepository(Property::class)->findOneBy(['folder' => $folder]);
    }

    /**
     * Saves documents to respective entities
     *
     * @param Folder $folder
     * @return Apartment|null
     */
    public function getApartment(Folder $folder): ?Apartment
    {
        $em = $this->doctrine->getManager();
        return $em->getRepository(Apartment::class)->findOneBy(['folder' => $folder]);
    }

    /**
     * Saves documents to respective entities
     *
     * @param Folder $folder
     * @return ObjectContracts|null
     */
    public function getContract(Folder $folder): ?ObjectContracts
    {
        $em = $this->doctrine->getManager();
        return $em->getRepository(ObjectContracts::class)->findOneBy(['folder' => $folder]);
    }

    /**
     * Saves documents to respective entities
     *
     * @param string $objectType
     * @param string $publicId
     * @return array
     * @throws EntityNotFoundException
     */
    public function getFolderObject(string $objectType, string $publicId): ?array
    {
        $em = $this->doctrine->getManager();
        $rootDir = $this->params->get('root_directory') . $this->params->get('file.root_path');
        switch ($objectType) {
            case 'property':
                $data['object'] = $em->getRepository(Property::class)->findOneBy(['publicId' => $publicId]);
                $data['folder'] = $rootDir . $data['object']->getFolderName() . '/' . Constants::PROPERTY_DOC_FOLDER;
                break;

            case 'apartment':
                $data['object'] = $em->getRepository(Apartment::class)->findOneBy(['publicId' => $publicId]);
                $propertyFolder = $data['object']->getProperty()->getFolderName();
                $data['folder'] = $rootDir . $propertyFolder . '/' . Constants::APARTMENT_DOC_FOLDER;
                break;

            default:
                throw new EntityNotFoundException('entityNotFound');
        }

        return $data;
    }

    /**
     * Gets corresponding entities to save documents
     *
     * @param string $objectType
     * @return string
     */
    private function getEntityName(string $objectType): string
    {
        $entity = '';
        switch ($objectType) {
            case 'propertyDocument':
                $entity = 'property';
                break;
            case 'apartmentDocument':
                $entity = 'apartment';
                break;
            default:
                break;
        }
        return ucfirst($entity);
    }

    /**
     * @param string $folderName
     * @param string $folderDisplayName
     * @param UserIdentity $user
     * @param string $path
     * @param bool $accessibility
     * @param Folder|null $parent
     * @param bool $isSystemGenerated
     * @param Role|null $createdByRole
     * @return Folder
     * @throws \Exception
     */
    private function saveFolderInfo(string $folderName, string $folderDisplayName, UserIdentity $user, string $path, bool $accessibility, ?Folder $parent = null, bool $isSystemGenerated = false, ?Role $createdByRole = null): Folder
    {
        $folder = new Folder();
        $requestKeys = ['path' => $path, 'displayName' => $folderDisplayName, 'createdBy' => $user, 'parent' => $parent,
            'isPrivate' => $accessibility, 'name' => $folderName, 'isSystemGenerated' => $isSystemGenerated,
            'createdRole' => $createdByRole];
        return $this->containerUtility->convertRequestKeysToSetters($requestKeys, $folder);
    }

    /**
     * @param string $string
     * @param bool $capitalizeFirstCharacter
     * @return string
     */
    public function convertSnakeCaseString(string $string, bool $capitalizeFirstCharacter = false): string
    {
        return $this->snakeToCamelCaseConverter($string, $capitalizeFirstCharacter);
    }

    /**
     * @param string $string
     * @return string
     */
    public function convertCamelCaseString(string $string): string
    {
        return $this->camelCaseConverter($string);
    }

    /**
     * @param int $user
     * @param string|null $parent
     * @param array $options
     * @param bool $isSearch
     * @return mixed
     * @throws \Exception
     */
    public function propertyAdminFolder(int $user, ?string $parent = null, array $options = [], bool $isSearch = false): array
    {
        $em = $this->doctrine->getManager();
        $users = array_merge($em->getRepository(Property::class)->findRelatedPropertyLevelUsers($user, $this->camelCaseConverter(Constants::PROPERTY_ADMIN_ROLE)),
            $em->getRepository(Property::class)->findRelatedObjectLevelUsers($user, $this->camelCaseConverter(Constants::PROPERTY_ADMIN_ROLE)));
        array_push($users, $user);

        return $this->getFoldersAndDocuments($user, $users, $this->camelCaseConverter(Constants::PROPERTY_ADMIN_ROLE), $parent, false, $options);
    }

    /**
     * @param int $user
     * @param string|null $parent
     * @param array $options
     * @param bool $isSearch
     * @return mixed
     * @throws \Exception
     */
    public function adminFolder(int $user, ?string $parent = null, array $options = [], bool $isSearch = false): array
    {
        $em = $this->doctrine->getManager();
        $owners = $em->getRepository(Property::class)->findOwners($user);
        array_push($owners, $user);

        return $this->getFoldersAndDocuments($user, $owners, Constants::ADMIN_ROLE, $parent, false, $options);
    }

    /**
     * @param int $user
     * @param string|null $parent
     * @param array $options
     * @param bool $isSearch
     * @return mixed
     * @throws \Exception
     */
    public function ownerFolder(int $user, ?string $parent = null, array $options = [], bool $isSearch = false): array
    {
        $em = $this->doctrine->getManager();
        $users = array_merge($em->getRepository(Property::class)->findRelatedPropertyLevelUsers($user, Constants::OBJECT_OWNER_ROLE),
            $em->getRepository(Property::class)->findRelatedObjectLevelUsers($user, Constants::OBJECT_OWNER_ROLE));
        array_push($users, $user);

        return $this->getFoldersAndDocuments($user, $users, Constants::OWNER_ROLE, $parent, false, $options);
    }

    /**
     * @param int $user
     * @param string|null $parent
     * @param array $options
     * @param bool $isSearch
     * @return mixed
     * @throws \Exception
     */
    public function tenantFolder(int $user, ?string $parent = null, array $options = [], bool $isSearch = false): array
    {
        $em = $this->doctrine->getManager();
        $tenantRole = $em->getRepository(Role::class)->findOneBy(['roleKey' => Constants::TENANT_ROLE]);
        $propertyUser = $em->getRepository(PropertyUser::class)->findBy(['user' => $user, 'role' => $tenantRole, 'isActive' => true, 'deleted' => false]);
        $contracts = $properties = $users = [];
        if (!empty($propertyUser)) {
            foreach ($propertyUser as $pUser) {
                $contracts[] = $pUser->getContract()->getIdentifier();
                $properties[] = $pUser->getProperty()->getIdentifier();
            }
            $options['properties'] = array_unique(array_filter($properties, fn($value) => !is_null($value) && $value !== ''));
            if (!empty($contracts)) {
                $usersInContract = $em->getRepository(PropertyUser::class)->findBy(['contract' => $contracts, 'isActive' => true, 'deleted' => false]);
                if (!empty($usersInContract)) {
                    foreach ($usersInContract as $contractUser) {
                        if ($contractUser instanceof PropertyUser) {
                            $users[] = $contractUser->getUser()->getIdentifier();
                            $users[] = $contractUser->getProperty() instanceof Property ?
                                $contractUser->getProperty()->getUser()->getIdentifier() : null;
                            $users[] = $contractUser->getProperty() instanceof Property ?
                                ($contractUser->getProperty()->getAdministrator() instanceof UserIdentity ?
                                    $contractUser->getProperty()->getAdministrator()->getIdentifier() : null) : null;
                            $users[] = $contractUser->getProperty() instanceof Property ?
                                ($contractUser->getProperty()->getJanitor() instanceof UserIdentity ?
                                    $contractUser->getProperty()->getJanitor()->getIdentifier() : null) : null;
                        }
                    }
                }
            }
        }
        return $this->getFoldersAndDocuments($user, array_unique(array_filter($users, fn($value) => !is_null($value) && $value !== '')), Constants::TENANT_ROLE, $parent, true, $options);
    }

    /**
     * @param int $user
     * @param string|null $parent
     * @param array $options
     * @param bool $isSearch
     * @return mixed
     * @throws \Exception
     */
    public function companyFolder(int $user, ?string $parent = null, array $options = [], bool $isSearch = false): array
    {
        $users = (array)$user;

        return $this->getFoldersAndDocuments($user, $users, Constants::COMPANY_ROLE, $parent, true, $options);
    }

    /**
     * @param int $user
     * @param string|null $parent
     * @param array $options
     * @param bool $isSearch
     * @return mixed
     * @throws \Exception
     */
    public function janitorFolder(int $user, ?string $parent = null, array $options = [], bool $isSearch = false): array
    {
        $em = $this->doctrine->getManager();
        $users = array_merge($em->getRepository(Property::class)->findRelatedPropertyLevelUsers($user, Constants::JANITOR_ROLE), $em->getRepository(Property::class)->findRelatedObjectLevelUsers($user, Constants::JANITOR_ROLE));
        array_push($users, $user);

        return $this->getFoldersAndDocuments($user, $users, Constants::JANITOR_ROLE, $parent, true, $options);
    }

    /**
     * @param int $user
     * @param string|null $parent
     * @param array $options
     * @param bool $isSearch
     * @return mixed
     * @throws \Exception
     */
    public function companyUserFolder(int $user, ?string $parent = null, array $options = [], bool $isSearch = false): array
    {
        $users = (array)$user;

        return $this->getFoldersAndDocuments($user, $users, Constants::COMPANY_USER_ROLE, $parent, true, $options);
    }

    /**
     * @param int $user
     * @param string|null $parent
     * @param array $options
     * @param bool $isSearch
     * @return mixed
     * @throws \Exception
     */
    public function objectOwnerFolder(int $user, ?string $parent = null, array $options = [], bool $isSearch = false): array
    {
        $em = $this->doctrine->getManager();
        $objectOwnerRoleKey = $this->convertCamelCaseString(Constants::OBJECT_OWNER_ROLE);
        $objectOwnerRole = $em->getRepository(Role::class)->findOneBy(['roleKey' => $objectOwnerRoleKey]);
        $propertyUser = $em->getRepository(PropertyUser::class)->findBy(['user' => $user, 'role' => $objectOwnerRole, 'isActive' => true, 'deleted' => false]);
        $contracts = $properties = $users = [];
        if (!empty($propertyUser)) {
            foreach ($propertyUser as $pUser) {
                $contracts[] = $pUser->getContract()->getIdentifier();
                $properties[] = $pUser->getProperty()->getIdentifier();
            }
            $options['properties'] = array_unique(array_filter($properties, fn($value) => !is_null($value) && $value !== ''));
            if (!empty($contracts)) {
                $usersInContract = $em->getRepository(PropertyUser::class)->findBy(['contract' => $contracts, 'isActive' => true, 'deleted' => false]);
                if (!empty($usersInContract)) {
                    foreach ($usersInContract as $contractUser) {
                        if ($contractUser instanceof PropertyUser) {
                            $users[] = $contractUser->getUser()->getIdentifier();
                            $users[] = $contractUser->getProperty() instanceof Property ?
                                $contractUser->getProperty()->getUser()->getIdentifier() : null;
                            $users[] = $contractUser->getProperty() instanceof Property ?
                                ($contractUser->getProperty()->getAdministrator() instanceof UserIdentity ?
                                    $contractUser->getProperty()->getAdministrator()->getIdentifier() : null) : null;
                            $users[] = $contractUser->getProperty() instanceof Property ?
                                ($contractUser->getProperty()->getJanitor() instanceof UserIdentity ?
                                    $contractUser->getProperty()->getJanitor()->getIdentifier() : null) : null;
                        }
                    }
                }
            }
        }
        return $this->getFoldersAndDocuments($user, array_unique(array_filter($users, fn($value) => !is_null($value) && $value !== '')), $objectOwnerRoleKey, $parent, true, $options);
    }

    /**
     * @param int $user
     * @param string|null $parent
     * @param array $options
     * @param bool $isSearch
     * @return mixed
     * @throws \Exception
     */
    public function guestFolder(int $user, ?string $parent = null, array $options = [], bool $isSearch = false): array
    {
        $users = (array)$user;

        return $this->getFoldersAndDocuments($user, $users, Constants::COMPANY_ROLE, $parent, true, $options);
    }

    /**
     * @param int $user
     * @param array $users
     * @param string $role
     * @param string|null $parent
     * @param bool $isRestricted
     * @param array $options
     * @return array
     * @throws \Exception
     */
    protected function getFoldersAndDocuments(int $user, array $users, string $role, ?string $parent = null, bool $isRestricted = false, array $options = []): array
    {
        $em = $this->doctrine->getManager();
        $role = $em->getRepository(Role::class)->findOneBy(['roleKey' => $this->camelCaseConverter($role)]);
        $data['folders'] = $em->getRepository(Folder::class)->getFolders($user, $users, $parent, $isRestricted, $options, $role);
        foreach ($data['folders'] as $key => $value) {
            $property = $em->getRepository(Property::class)->findOneBy(['identifier' => $value['propertyId']]);
            $data['folders'][$key]['cancelledOrExpired'] = !is_null($property) ? $this->checkPropertyCancelledOrExpired($property) : '';
            if (isset($value['isEditable']))
                $data['folders'][$key]['isEditable'] = (bool)$value['isEditable'];
            if (isset($value['isPropertyActive']))
                $data['folders'][$key]['isPropertyActive'] = (bool)$value['isPropertyActive'];
            $userLanguage = $em->getRepository(UserIdentity::class)->findOneBy(['identifier' => $user]);
            if ($userLanguage->getLanguage() == 'de' && $value['isSystemGenerated'] == true &&
                $data['folders'][$key]['displayName'] == Constants::SYSTEM_GENERATED_OBJECT) {
                $data['folders'][$key]['displayName'] = Constants::SYSTEM_GENERATED_OBJECT_DE;
            }
        }
        if (!is_null($parent) && empty($options)) {
            $folder = $em->getRepository(Folder::class)->findOneBy(['publicId' => $parent, 'deleted' => false]);
            if ($folder instanceof Folder) {
                $documents = $em->getRepository(Document::class)->getDocuments($user, $users, $isRestricted, $folder->getIdentifier(), $options, $role);
                $damageImages = $em->getRepository(DamageImage::class)->getDocuments($users, $isRestricted, $folder->getIdentifier(), $options);
                $documents = array_merge($damageImages, $documents);
                $data['documents'] = $this->getDocumentsList($documents);
            }
        } else {
            $folder = isset($options['folder']) ? $em->getRepository(Folder::class)->findOneBy(['publicId' => $options['folder'], 'deleted' => false]) : null;
            $folderId = null !== $folder ? $folder->getIdentifier() : null;
            $documents = $em->getRepository(Document::class)->getDocuments($user, $users, $isRestricted, $folderId, $options, $role);
            $damageImages = $em->getRepository(DamageImage::class)->getDocuments($users, $isRestricted, $folderId, $options);
            $documents = array_merge($damageImages, $documents);
            $data['documents'] = $this->getDocumentsList($documents);
        }

        return $data;
    }

    /**
     * @param Property $property
     * @return string
     */
    public function checkPropertyCancelledOrExpired(Property $property): string
    {
        if (!is_null($property->getExpiredDate())) {
            return Constants::PROPERTY_SUBSCRIPTION_EXPIRED;
        }
        if ($property->getIsCancelledSubscription() == true) {
            return Constants::PROPERTY_SUBSCRIPTION_CANCELLED;
        }
        return Constants::PROPERTY_ACTIVE;
    }

    /**
     * @param string $parent
     * @param string $locale
     * @return array
     */
    public function getParentFolders(string $parent, string $locale): array
    {
        $em = $this->doctrine->getManager();
        return $em->getRepository(Folder::class)->getParentFolders($parent, $locale);
    }

    /**
     * @param string $uuid
     * @param UserIdentity $user
     * @return bool
     */
    public function checkIsEditPermissionGranted(string $uuid, UserIdentity $user): bool
    {

    }

    /**
     *
     * @param ReturnableDocumentInterface|null $fileInfo
     * @param string $baseUrl
     * @param bool|null $encoding
     * @return array
     * @throws \Exception
     */
    public function getUploadInfo(?ReturnableDocumentInterface $fileInfo, string $baseUrl, ?bool $encoding = true): array
    {
        $data = array();
        if ($fileInfo instanceof Document) {
            $data['identifier'] = $fileInfo->getIdentifier();
            $data['publicId'] = $fileInfo->getPublicId();
            $data['originalName'] = $fileInfo->getOriginalName();
            $data['path'] = $baseUrl . '/' . $fileInfo->getPath();
            $data['displayName'] = $this->removeFileExtension($fileInfo->getDisplayName());
            $data['type'] = $fileInfo->getType();
            $data['filePath'] = $fileInfo->getStoredPath();
            $data['isPrivate'] = $fileInfo->getIsPrivate() ? 'private' : 'public';
            $data['mimeType'] = $fileInfo->getMimeType();
            $data['size'] = $fileInfo->getSize();
            $data['folder'] = $fileInfo->getFolder()->getPublicId();
            $data['thumbnails'] = $this->getThumbnails($fileInfo->getOriginalName(), $data['path']);
            $params = ['path' => $data['filePath'], 'mimeType' => $data['mimeType']];
            if (in_array($data['mimeType'], Constants::EXCEPTED_MIME_TYPES)) {
                if ($encoding) {
                    $data['encodedData'] = $this->getDocumentEncodedData($params);
                }
//                $data['path'] = $this->getDocumentViewUrl(str_replace($this->params->get('root_directory'), '/', $fileInfo->getStoredPath()), $baseUrl, $fileInfo->getMimeType());
                $data['path'] = $this->getDocumentViewUrl('/' . $fileInfo->getPath(), $baseUrl, $fileInfo->getMimeType());
            } elseif ($encoding) {
                // To uncomment on next push
                $params = [
                    'path' => $this->checkFileExists($fileInfo->getMimeType(), $fileInfo->getStoredPath()),
                    'mimeType' => $fileInfo->getMimeType()
                ];
                $data['encodedData'] = $this->fileUploaderUtility->fileToBase64($params);
            }

        } else if ($fileInfo instanceof TemporaryUpload) {
            $data['identifier'] = $fileInfo->getIdentifier();
            $data['publicId'] = $fileInfo->getPublicId();
            $data['originalName'] = $fileInfo->getLocalFileName();
            $data['path'] = $baseUrl . '/' . $fileInfo->getFilePath();
            $data['displayName'] = explode('.', $fileInfo->getOriginalFileName())[0];
            $data['type'] = $fileInfo->getDocType();
            $data['filePath'] = $fileInfo->getTemporaryUploadPath();
            $data['mimeType'] = $fileInfo->getMimeType();
            $data['size'] = $fileInfo->getFileSize();
            if (str_starts_with($fileInfo->getMimeType(), 'image')) {
                $data['thumbnails'] = $this->getThumbnails($fileInfo->getLocalFileName(), $data['path']);
            }
            $params = ['path' => $fileInfo->getTemporaryUploadPath(), 'mimeType' => $data['mimeType']];
            if (in_array($data['mimeType'], Constants::EXCEPTED_MIME_TYPES)) {
                if ($encoding) {
                    $data['encodedData'] = $this->getDocumentEncodedData($params);
                }
//                $data['path'] = $this->getDocumentViewUrl(str_replace($this->params->get('root_directory'), '/', $fileInfo->getStoredPath()), $baseUrl, $fileInfo->getMimeType());
                $data['path'] = $this->getDocumentViewUrl('/' . $fileInfo->getFilePath(), $baseUrl, $fileInfo->getMimeType());
            } elseif ($encoding) {
                $data['encodedData'] = $this->fileUploaderUtility->fileToBase64($params);
            }
        }
        return $data;
    }

    /**
     * @param string $type
     * @param string $publicId
     * @return ReturnableDocumentInterface
     */
    public function fetch(string $type, string $publicId): ?ReturnableDocumentInterface
    {
        $value = null;
        $em = $this->doctrine->getManager();
        switch ($type) {
            case 'temporary':
                $value = $em->getRepository(TemporaryUpload::class)->findOneBy(['publicId' => $publicId]);
                break;
            case 'document':
                $value = $em->getRepository(Document::class)->findOneBy(['publicId' => $publicId]);
                break;
            case 'folder':
                $value = $em->getRepository(Folder::class)->findOneBy(['publicId' => $publicId]);
                break;
            case 'ticket':
                $value = $em->getRepository(DamageImage::class)->findOneBy(['publicId' => $publicId]);
                break;
            case 'message':
                $value = $em->getRepository(MessageDocument::class)->findOneBy(['publicId' => $publicId]);
                break;
            default:
                throw new InvalidArgumentException('invalidType');
        }
        return $value;
    }

    /**
     * persistDocument
     *
     * Function to save documents against property
     *
     * @param array $documents
     * @param object $oObject
     * @param string|null $folder
     * @return void
     *
     * @throws \Exception
     */
    public function persistDocument(array $documents, object $oObject, ?string $folder = ''): void
    {
        if (!empty($documents)) {
            $em = $this->doctrine->getManager();
            foreach ($documents as $docId) {
                $path = $this->params->get('property_path') . '/';
                $tempDocObject = $em->getRepository(TemporaryUpload::class)->findOneBy(['publicId' => $docId]);
                if ($tempDocObject instanceof TemporaryUpload) {
                    $fs = new Filesystem();
                    $path = $path . $this->getFolderPath($oObject->getFolder()->getPath()) . '/' . $folder . '/' . $tempDocObject->getLocalFileName();
                    $filePath = $oObject->getFolder()->getPath() . '/' . $folder . '/' . $tempDocObject->getLocalFileName();
                    if (!empty($folder)) {
                        $newFolder = $oObject->getFolder()->getPath() . '/' . $folder;
                        $this->createEmptyFolder($newFolder);
                        $filePath = $newFolder . '/' . $tempDocObject->getLocalFileName();
                    }
                    $this->moveDocument($tempDocObject, $filePath, $path, $oObject);
                    $fs->rename($tempDocObject->getTemporaryUploadPath(), $filePath);
                    $this->fileUploaderUtility->optimizeFile($filePath, $tempDocObject->getMimeType());
                    $em->remove($tempDocObject);
                }
            }
            $em->flush();
        }
    }

    /**
     *
     * @param TemporaryUpload $temporaryUpload
     * @param string $filePath
     * @param string|null $path
     * @param object|null $oObject
     * @param UserIdentity|null $userIdentity
     * @return void
     * @throws \Exception
     */
    public function moveDocument(TemporaryUpload $temporaryUpload, string $filePath, ?string $path = null, ?object $oObject = null, ?UserIdentity $userIdentity = null): void
    {
        if (!$oObject instanceof Folder) {
            $folderObj = $oObject->getFolder();
        } else {
            $folderObj = $oObject;
            $this->removeCompanyLogo($oObject);
        }
        $data = [
            'type' => $temporaryUpload->getDocType(),
            'size' => $temporaryUpload->getFileSize(),
            'displayName' => $temporaryUpload->getOriginalFileName(),
            'mimeType' => $temporaryUpload->getMimeType(),
            'path' => $path,
            'title' => $temporaryUpload->getLocalFileName(),
            'originalName' => $temporaryUpload->getLocalFileName(),
            'storedPath' => $filePath,
            'folder' => $folderObj
        ];

        if ('floorPlan' == $temporaryUpload->getDocType()) {
            $extension = '';
            if ($temporaryUpload->getMimeType() == 'image/jpeg') {
                $extension = '.jpg';
            } else if ($temporaryUpload->getMimeType() == 'image/png') {
                $extension = '.png';
            }
            $data['originalName'] = preg_replace('/\./', '', $data['originalName']) . $extension;
            $data['displayName'] = preg_replace('/\./', '', $data['displayName']) . $extension;
            $data['title'] = preg_replace('/\./', '', $data['title']) . $extension;
        }

        $document = $this->containerUtility->convertRequestKeysToSetters($data, new Document());
        if ($oObject instanceof Property) {
            $document->setProperty($oObject)
                ->setUser($oObject->getUser());
        } elseif ($oObject instanceof Apartment) {
            $document->setApartment($oObject)
                ->setIsPrivate(false)
                ->setUser($oObject->getCreatedBy());
        } elseif ($oObject instanceof ObjectContracts) {
            $document->setContract($oObject)
                ->setUser($oObject->getObject()->getCreatedBy());
        } else {
            $document->setUser($userIdentity);
        }
    }

    /**
     * @param string $path
     * @return string
     */
    public function getFolderPath(string $path): string
    {
        $segments = array();
        $pathArray = explode('/', $path);
        if (!empty($pathArray)) {
            foreach ($pathArray as $segment) {
                if (strpos($segment, 'folder-') !== false) {
                    $segments[] = $segment;
                }
            }
        }
        return implode('/', $segments);
    }

    /**
     * Function to get document view URL
     * @param string $path
     * @param string $baseUrl
     * @param string|null $mimeType
     *
     * @return string
     */
    public function getDocumentViewUrl(string $path, string $baseUrl, ?string $mimeType): string
    {
        if (null !== $mimeType && str_starts_with($mimeType, 'image')) {
            $url = $baseUrl . $path;
        } else {
            $url = $baseUrl . $this->router->generate('balu_view_document', array('path' => $path));
        }

        return $url;
    }

    /**
     *
     * @param object $object
     * @return void
     */
    public function deleteCoverImageIfExists(object $object): void
    {
        $em = $this->doctrine->getManager();
        if ($object instanceof Property) {
            $images = $em->getRepository(Document::class)->findBy(['property' => $object, 'deleted' => false, 'type' => 'coverImage']);
        } else if ($object instanceof Apartment) {
            $images = $em->getRepository(Document::class)->findBy(['deleted' => false, 'apartment' => $object, 'type' => 'coverImage']);
        }
        if (!empty($images)) {
            foreach ($images as $image) {
                $image->setDeleted(true);
            }
        }
    }

    /**
     *
     * @param string $name
     * @return void
     */
    public function createEmptyFolder(string $name): void
    {
        $fs = new Filesystem();
        if (!$fs->exists($name)) {
            $fs->mkdir($name . '/', 0755);
        }
    }

    /**
     *  Function to get thumbnails
     *
     * @param string $fileName
     * @param string $path
     * @return array
     */
    public function getThumbnails(string $fileName, string $path): array
    {
        $resizedFile = [];
        $imageDimensions = $this->parameterBag->get('image_sizes');
        foreach ($imageDimensions as $key => $imageDimension) {
            $dimension = explode('*', $imageDimension);
            list($width, $height) = $dimension;

            $newFileName = str_replace($fileName, '', $path) . $width . '-' . $height . '-' . basename($fileName);
            $resizedFile["image_$width" . "X$height"] = $newFileName;
        }
        return $resizedFile;
    }

    /**
     *
     * @param string $mimeType
     * @param string $path
     * @param string|null $request
     * @return string|null
     * @throws FileNotFoundException
     */
    public function checkFileExists(string $mimeType, string $path, ?string $request = ''): ?string
    {
        $fs = new Filesystem();
        $pathParts = pathinfo($path);
        if (str_starts_with($mimeType, 'image')) {
            $storedPath = $path;
        } else {
            $storedPath = str_replace($pathParts['extension'], 'zip', $path);
            $path = $this->getDocumentViewUrl(str_replace($this->params->get('root_directory'), '/', $path), $request, $mimeType);
        }
        if (!$fs->exists($storedPath)) {
            throw new FileNotFoundException('fileNotFound');
        }

        return $path;
    }

    /**
     *
     * @param array $params
     * @return array
     * @throws \PhpZip\Exception\ZipException
     * @throws \Exception
     */
    public function getDocumentEncodedData(array $params): array
    {
        $path = $params['path'];
        $pathParts = pathinfo($path);
        $compressedFile = $pathParts['dirname'] . '/' . $pathParts['filename'] . '.zip';
        $zipExists = false;
        $fs = new Filesystem();
        if ($fs->exists($compressedFile)) {
            $zipExists = true;
            $this->fileUploaderUtility->decompressFile($compressedFile);
        }
        $content = $this->fileUploaderUtility->fileToBase64($params);
        if ($zipExists && $fs->exists($path)) {
            $fs->remove($path);
        }

        return $content;
    }

    /**
     *
     * @param string $parent
     * @return string
     */
    public function getAccessibility(string $parent): string
    {
        $em = $this->doctrine->getManager();
        $folder = $em->getRepository(Folder::class)->findOneBy(['publicId' => $parent, 'deleted' => false]);

        return $folder->getIsPrivate() ? 'private' : 'public';
    }

    /**
     * @param array $documents
     * @return array
     * @throws \Exception
     */
    private function getDocumentsList(array $documents): array
    {
        $data = [];
        if (!empty($documents)) {
            $fs = new Filesystem();
            foreach ($documents as $document) {
                $pathParts = pathinfo($document['storedPath']);
                $extensionPos = strrpos($document['storedPath'], $pathParts['extension']);
                if ($extensionPos !== false) {
                    $zip = substr_replace($document['storedPath'], 'zip', $extensionPos, strlen($pathParts['extension']));
                }
                if ($fs->exists($document['storedPath']) || $fs->exists($zip)) {
                    if (isset($document['damageId'])) {
                        $document['path'] = $this->getDocumentViewUrl(str_replace($this->params->get('damage_path'), '/', $document['path']), $this->request->getCurrentRequest()->getSchemeAndHttpHost(), $document['mimeType']);
                    } else {
                        $document['path'] = $this->getDocumentViewUrl('/' . $document['path'], $this->request->getCurrentRequest()->getSchemeAndHttpHost(), $document['mimeType']);
                    }
//                    $document['encodedData'] = (in_array($document['mimeType'], Constants::EXCEPTED_MIME_TYPES)) ? $this->getDocumentEncodedData($document['storedPath']) : $this->fileUploaderUtility->fileToBase64($document['storedPath']);
                    $document['thumbnails'] = (null !== $document['mimeType'] && str_starts_with($document['mimeType'], 'image')) ? $this->getThumbnails($pathParts['basename'], $document['path']) : null;
                    $document['isEditable'] = (bool)$document['isEditable'];
                    $data[] = $document;
                }
            }
        }

        return $data;
    }

    /**
     *
     * @param string|null $filename
     * @return string
     */
    public function removeFileExtension(?string $filename): ?string
    {
        $pathParts = pathinfo($filename);
        $extensionPos = isset($pathParts['extension']) ? strrpos($filename, '.' . $pathParts['extension']) : false;
        if ($extensionPos !== false) {
            $filename = substr_replace($filename, '', $extensionPos, strlen('.' . $pathParts['extension']));
        }

        return $filename;

    }

    /**
     * @param object|null $document
     * @param array $entities
     * @return bool
     */
    public function checkInstance(?object $document, array $entities = []): bool
    {
        foreach ($entities as $entity) {
            $entityClass = 'App\Entity\\' . $entity;
            if (is_object($document) && $document instanceof $entityClass) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param TemporaryUpload $document
     * @param UserIdentity $user
     * @param Role $role
     * @return void
     * @throws \PhpZip\Exception\ZipException
     * @throws \Exception
     */
    public function persistCompanyLogo(TemporaryUpload $document, UserIdentity $user, Role $role): void
    {
        $em = $this->doctrine->getManager();
        $path = $this->params->get('company_logo_path');
        $fs = new Filesystem();
        $folderName = $user->getCompanyName() . '-' . $user->getIdentifier();
        $folderObj = $em->getRepository(Folder::class)->findOneBy(['displayName' => ucfirst($folderName)], ['identifier' => 'DESC']);
        if ((!$folderObj instanceof Folder) || (!$fs->exists($folderObj->getPath()))) {
            $folderDetail = $this->createFolder($folderName, $user, true, null, false, false, $role);
            $folderObj = $em->getRepository(Folder::class)->findOneBy(['identifier' => $folderDetail[0]['identifier']]);
        }
        $filePath = $folderObj->getPath() . '/' . $document->getLocalFileName();
        $fileAccessPathArray = explode('/', $path);
        array_shift($fileAccessPathArray);
        $fileAccessPath = implode('/', $fileAccessPathArray) . $folderObj->getName() . '/' . $document->getLocalFileName();
        $this->moveDocument($document, $filePath, $fileAccessPath, $folderObj, $user);
        $fs->rename($document->getTemporaryUploadPath(), $filePath);
        $this->fileUploaderUtility->optimizeFile($filePath, $document->getMimeType());
        $em->flush();
        $em->remove($document);
        $em->flush();
    }

    /**
     * @param Folder|null $folder
     */
    public function removeCompanyLogo(?Folder $folder = null): void
    {
        $em = $this->doctrine->getManager();
        if (!$folder instanceof Folder) {
            $currentUser = $this->containerUtility->getSecurityService()->getUser();
            $folder = $em->getRepository(Folder::class)->getCompanyLogoFolder($currentUser);
        }
        $existingDocs = $em->getRepository(Document::class)->findBy(['folder' => $folder, 'isActive' => true]);
        if (!empty($existingDocs)) {
            foreach ($existingDocs as $doc) {
                if ($doc instanceof Document) {
                    $doc->setIsActive(false);
                }
            }
            $em->flush();
        }
    }
}
