<?php

/**
 * This file is part of the Wedoit Project package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Helpers;

use App\Entity\Document;
use App\Entity\Folder;
use App\Entity\Interfaces\ReturnableDocumentInterface;
use App\Entity\TemporaryUpload;
use App\Entity\UserIdentity;
use App\Utils\FileUploaderUtility;
use App\Utils\ContainerUtility;
use Spatie\PdfToImage\Pdf;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Service\DMSService;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Damage;
use App\Utils\Constants;
use App\Entity\DamageImage;
use App\Exception\FormErrorException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * FileUploadHelper
 *
 * Helper class to handle file upload actions
 *
 * @package         Balu property app 2
 * @subpackage      App
 * @author          Rahul <rahul.rl@pitsolutions.com>
 */
class FileUploadHelper
{
    /**
     * @var FileUploaderUtility $fileUploaderUtility
     */
    private FileUploaderUtility $fileUploaderUtility;

    /**
     * @var ParameterBagInterface $params
     */
    private ParameterBagInterface $params;

    /**
     * @var DMSService $dmsService
     */
    private DMSService $dmsService;

    /**
     * @var ContainerUtility
     */
    private ContainerUtility $containerUtility;

    /**
     * @var EntityManagerInterface $em
     */
    private EntityManagerInterface $em;

    /**
     * Constructor
     *
     * @param ContainerUtility $containerUtility
     * @param FileUploaderUtility $fileUploaderUtility
     * @param ParameterBagInterface $params
     * @param DMSService $dmsService
     */
    public function __construct(ContainerUtility $containerUtility, FileUploaderUtility $fileUploaderUtility, ParameterBagInterface $params, DMSService $dmsService)
    {
        $this->containerUtility = $containerUtility;
        $this->fileUploaderUtility = $fileUploaderUtility;
        $this->params = $params;
        $this->dmsService = $dmsService;
        $this->em = $containerUtility->getEntityManager();
    }

    /**
     * Function to upload documents
     *
     * @param UserIdentity $user
     * @param string $baseUrl
     * @param array $files
     * @param array $aData
     * @return array $aData
     * @throws \Exception
     */
    public function uploadDocument(UserIdentity $user, string $baseUrl, array $files, array $aData): array
    {
        if ($aData['type'] === 'ticket') {
            return $this->uploadTicketDocs($user, $aData, $files, $baseUrl);
        }
        $data = [];
        $fileCount = 0;
        $pdfToJpg = isset($aData['pdfToJpg']) && (!empty($aData['pdfToJpg']) && $aData['pdfToJpg'] == 1);
        $page = (isset($aData['page']) && !empty($aData['page'])) ? $aData['page'] : 1;
        $maxFileLimit = $this->params->get('max_upload_count');
        $aData['folder'] = $this->dmsService->getFolder($aData['folder']);
        $aData['property'] = (isset($aData['type']) && ('property' === $aData['type'] || 'coverImage' === $aData['type'])) ? $this->dmsService->getProperty($aData['folder']) : null;
        $aData['apartment'] = (isset($aData['type']) && ('apartment' === $aData['type'] || 'coverImage' === $aData['type'])) ? $this->dmsService->getApartment($aData['folder']) : null;
        $aData['floorPlan'] = (isset($aData['type']) && 'floorPlan' === $aData['type']) ? $this->dmsService->getApartment($aData['folder']) : null;
        $aData['contract'] = (isset($aData['type']) && 'contract' === $aData['type']) ? $this->dmsService->getContract($aData['folder']) : null;
        if ($aData['type'] === 'coverImage' && isset($aData['apartment'])) {
            $this->dmsService->deleteCoverImageIfExists($aData['apartment']);
        } else if ($aData['type'] === 'coverImage' && isset($aData['property'])) {
            $this->dmsService->deleteCoverImageIfExists($aData['property']);
        }
        if (isset($files['files']) && $aData['folder'] instanceof Folder) {
            foreach ($files['files'] as $key => $resource) {
                if ($fileCount > $maxFileLimit) {
                    throw new \Exception('maxUploadError');
                }
                if ($pdfToJpg == true) {
                    $fileName = $aData['fileName'] ? $aData['fileName'] : 'converted';
                    $filesystem = new Filesystem();
                    $jpgPath = 'files/documents_temp/floorPlan/jpg';
                    if (!$filesystem->exists($jpgPath)) {
                        $filesystem->mkdir($jpgPath, 0755);
                    }
                    $pdf = new Pdf($resource);
                    $pdf->setCompressionQuality(75);
                    $pdf->setOutputFormat('jpeg');
                    $pdf->setPage((int)$page);
                    $timestamp = uniqid(strtotime('now'));
                    $pdf->saveImage($jpgPath . '/' . $fileName . '-' . $timestamp . '.jpg');
                    $file = new File($jpgPath . '/' . $fileName . '-' . $timestamp . '.jpg');
                    $upload = $this->fileUploaderUtility->uploadJpgFile($file, $aData['folder']->getPath(), $aData['type'], false, $aData['fileName']);
                } else {
                    $upload = $this->fileUploaderUtility->upload($resource, $aData['folder']->getPath(), $aData['type'], false, $aData['fileName']);
                }
                $fileInfo = $this->saveFileInfo($user, $upload, $aData);
                $this->fileUploaderUtility->optimizeFile($upload['path'], $upload['type']);
                $data[] = $this->dmsService->getUploadInfo($fileInfo, $baseUrl, $aData['isEncode']);
                $fileCount++;
            }
        }

        return $data;
    }

    /**
     * Function to upload documents
     *
     * @param string $objectType
     * @param array $files
     * @param string $baseUrl
     * @param bool|null $isEncode
     * @param bool|null $pdfToJpg
     * @param string|null $page
     * @param array $aData
     * @return array
     * @throws \PhpZip\Exception\ZipException
     * @throws \Spatie\PdfToImage\Exceptions\InvalidFormat
     * @throws \Spatie\PdfToImage\Exceptions\PageDoesNotExist
     * @throws \Spatie\PdfToImage\Exceptions\PdfDoesNotExist
     * @throws \Exception
     */
    public function tempUploadDocument(string $objectType, array $files, string $baseUrl, ?bool $isEncode = false, ?bool $pdfToJpg = false, ?string $page = '1', array $aData): array
    {
        $data = [];
        $fileCount = 0;
        $maxFileLimit = $this->params->get('max_upload_count');
        $path = $this->params->get('temp_upload_reference');
        if (isset($files['files'])) {
            foreach ($files['files'] as $key => $resource) {
                if ($fileCount > $maxFileLimit) {
                    throw new \Exception('maxUploadError');
                }
                $tempUpload = $this->fileUploaderUtility->upload($resource, $path, $objectType);
                if (str_starts_with($tempUpload['type'], 'image')) {
                    $this->fileUploaderUtility->optimizeFile($tempUpload['path'], $tempUpload['type']);
                }
                $tempUpload['mimeType'] = $tempUpload['type'];
                $fileInfo = $this->saveTempFileInfo($tempUpload, $objectType);
                if ($pdfToJpg == true) {
                    $tempJpgFile = $this->saveFileAsJpg($tempUpload, $objectType, $page, $aData);
                    $fileInfo = $this->saveTempFileInfo($tempJpgFile, $objectType);
                    $data[] = $this->dmsService->getUploadInfo($fileInfo, $baseUrl, $isEncode);
                    $fileCount++;
                } else {
                    $data[] = $this->dmsService->getUploadInfo($fileInfo, $baseUrl, $isEncode);
                    $fileCount++;
                }
            }
        }
        return $data;
    }

    /**
     * @param array $tempUpload
     * @param string $objectType
     * @param string|null $page
     * @param array $aData
     * @return array
     * @throws \PhpZip\Exception\ZipException
     * @throws \Spatie\PdfToImage\Exceptions\InvalidFormat
     * @throws \Spatie\PdfToImage\Exceptions\PageDoesNotExist
     * @throws \Spatie\PdfToImage\Exceptions\PdfDoesNotExist
     */
    public function saveFileAsJpg(array $tempUpload, string $objectType, ?string $page, array $aData): array
    {
        $data = [];
        $pdf = new Pdf($tempUpload['filePath']);
        $pdf->setCompressionQuality(75);
        $pdf->setOutputFormat('jpeg');
        $pdf->setPage((int)$page);
        $safeFilename = transliterator_transliterate(
            'Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()',
            (isset($aData['fileName']) && !empty($aData['fileName'])) ? $aData['fileName'] : $tempUpload['originalName']
        );
        $fileName = $safeFilename . '-' . uniqid(strtotime('now')) . '.jpg';
        $pdf->saveImage('files/documents_temp/' . $objectType . '/' . $fileName);

        $file = new File('files/documents_temp/' . $objectType . '/' . $fileName);
        if ($file->isFile()) {
            $this->fileUploaderUtility->optimizeFile($tempUpload['path'], $tempUpload['type']);
            $data = ['originalName' => $file->getBasename(), 'path' => $file->getRealPath(), 'fileDisplayName' => $file->getFilename(),
                'type' => $file->getMimeType(), 'size' => $file->getSize(), 'filePath' => $file->getPathname()];
        }

        return $data;
    }

    /**
     * @param array $tempUpload
     * @param string $objectType
     * @return TemporaryUpload
     * @throws \Exception
     */
    private function saveTempFileInfo(array $tempUpload, string $objectType): TemporaryUpload
    {
        $temp = new TemporaryUpload();
        $requestKeys = ['docType' => $objectType, 'fileSize' => $tempUpload['size'], 'originalFileName' => $tempUpload['fileDisplayName'],
            'mimeType' => $tempUpload['mimeType'], 'temporaryUploadPath' => $tempUpload['path'], 'filePath' => $tempUpload['filePath'], 'localFileName' => $tempUpload['originalName']];
        return $this->containerUtility->convertRequestKeysToSetters($requestKeys, $temp);
    }

    /**
     * @param UserIdentity $userIdentity
     * @param array $uploadDetails
     * @param array $aData
     * @return array|mixed
     * @throws \Exception
     */
    private function saveFileInfo(UserIdentity $userIdentity, array $uploadDetails, array $aData): Document
    {
        if ('floorPlan' == $aData['type']) {
            $aData['apartment'] = $this->dmsService->getApartment($aData['folder']);
        }
        $isPrivate = isset($aData['permission']) && $aData['permission'] === 'private';

        $requestKeys = [
            'type' => $aData['type'],
            'size' => $uploadDetails['size'],
            'displayName' => $uploadDetails['fileDisplayName'],
            'mimeType' => $uploadDetails['type'],
            'path' => $uploadDetails['filePath'],
            'title' => $uploadDetails['originalName'],
            'user' => $userIdentity,
            'isPrivate' => $isPrivate,
            'originalName' => $uploadDetails['originalName'],
            'storedPath' => $uploadDetails['path'],
            'folder' => $aData['folder'],
            'property' => $aData['property'],
            'apartment' => $aData['apartment'],
            'contract' => $aData['contract'],
        ];
        $fileInfo = $this->containerUtility->convertRequestKeysToSetters($requestKeys, new Document());
        $this->checkReadAccess($fileInfo);

        return $fileInfo;
    }

    /**
     *
     * @param string $objectType
     * @param array $aData
     * @param string $baseUrl
     * @param bool|null $isEncode
     * @return array
     * @throws \Exception
     */
    public function uploadCameraTempDocument(string $objectType, array $aData, string $baseUrl, ?bool $isEncode = false): array
    {
        $path = $this->params->get('temp_upload_reference');
        $tempUpload = $this->fileUploaderUtility->base64ToFile($aData['fileData'], $aData['fileName'], $path, $objectType, true);
        $fileInfo = $this->saveTempFileInfo($tempUpload, $objectType);
        $this->fileUploaderUtility->optimizeFile($tempUpload['path'], $tempUpload['mimeType']);

        return $this->dmsService->getUploadInfo($fileInfo, $baseUrl, $isEncode);
    }

    /**
     *
     * @param UserIdentity $user
     * @param string $baseUrl
     * @param array $aData
     * @return array
     * @throws \Exception
     */
    public function uploadCameraDocument(UserIdentity $user, string $baseUrl, array $aData): array
    {
        if ($aData['type'] === 'ticket') {
            return $this->uploadTicketDocs($user, $aData, null, $baseUrl, $aData['fileData']);
        }
        $data = [];
        $aData['folder'] = $this->dmsService->getFolder($aData['folder']);
        $aData['property'] = (isset($aData['type']) && ('property' === $aData['type'] || 'coverImage' === $aData['type'])) ? $this->dmsService->getProperty($aData['folder']) : null;
        $aData['apartment'] = (isset($aData['type']) && ('apartment' === $aData['type'] || 'coverImage' === $aData['type'])) ? $this->dmsService->getApartment($aData['folder']) : null;
        $aData['floorPlan'] = (isset($aData['type']) && 'floorPlan' === $aData['type']) ? $this->dmsService->getApartment($aData['folder']) : null;
        $aData['contract'] = (isset($aData['type']) && 'contract' === $aData['type']) ? $this->dmsService->getContract($aData['folder']) : null;
        if ($aData['type'] === 'coverImage' && isset($aData['apartment'])) {
            $this->dmsService->deleteCoverImageIfExists($aData['apartment']);
        } else if ($aData['type'] === 'coverImage' && isset($aData['property'])) {
            $this->dmsService->deleteCoverImageIfExists($aData['property']);
        }
        if (isset($aData['fileData']) && $aData['folder'] instanceof Folder) {
            $upload = $this->fileUploaderUtility->base64ToFile($aData['fileData'], $aData['fileName'], $aData['folder']->getPath(), $aData['type'], false);
            $fileInfo = $this->saveFileInfo($user, $upload, $aData);
            $data = $this->dmsService->getUploadInfo($fileInfo, $baseUrl, $aData['isEncode']);
        }

        return $data;
    }

    /**
     * @param UserIdentity $user
     * @param array $aData
     * @param array $files
     * @param string $baseUrl
     * @return array
     * @throws \Exception
     */
    public function uploadTicketDocs(UserIdentity $user, array $aData, ?array $files = null, string $baseUrl, ?string $fileData = null): array
    {
        $subType = $this->params->get('image_category')['photos'];
        if (isset($aData['subType']) && isset($this->params->get('image_category')[$aData['subType']])) {
            $subType = $this->params->get('image_category')[$aData['subType']];
        }
        $data = [];
        $damage = $this->em->getRepository(Damage::class)->findOneByPublicId($aData['folder']);
        if (!empty($files['files']) || null !== $fileData) {
            $folderInfo = $this->getTicketDocFolder($damage, $user);
            $destinationFolder = $folderInfo['destinationFolder'];
            $ticketsFolder = $folderInfo['ticketsFolder'];
            $fileCount = count($this->em->getRepository(DamageImage::class)->findBy(['damage' => $damage, 'deleted' => 0, 'imageCategory' => $subType]));
            $maxFileLimit = $this->params->get('max_upload_count_ticket');
            if (!empty($files['files'])) {
                foreach ($files['files'] as $key => $resource) {
                    if ($fileCount >= $maxFileLimit) {
                        throw new FormErrorException('maxUploadError');
                    }
                    $upload = $this->fileUploaderUtility->upload($resource, $destinationFolder->getPath(), $aData['type'], false, $aData['fileName']);
                    $damageImage = $this->saveDamageFileInfo($damage, $upload, $destinationFolder, $ticketsFolder, $subType);
                    $data[] = $this->getDamageFileInfo($damageImage, $baseUrl);
                    $this->fileUploaderUtility->optimizeFile($upload['path'], $upload['type']);
                    $fileCount++;
                }
            }
            if (null !== $fileData) {
                $fileCount++;
                if ($fileCount >= $maxFileLimit) {
                    throw new FormErrorException('maxUploadError');
                }
                $upload = $this->fileUploaderUtility->base64ToFile($aData['fileData'], $aData['fileName'], $destinationFolder->getPath(), $aData['type'], false);
                $damageImage = $this->saveDamageFileInfo($damage, $upload, $destinationFolder, $ticketsFolder, $subType);
                $data[] = $this->getDamageFileInfo($damageImage, $baseUrl);
                $this->fileUploaderUtility->optimizeFile($upload['path'], $upload['type']);
            }
            $this->em->flush();
        }

        return $data;
    }

    /**
     * @param Damage $damage
     * @param array $upload
     * @param Folder $destinationFolder
     * @param Folder $ticketsFolder
     * @param string $subType
     * @return DamageImage
     * @throws \Exception
     */
    private function saveDamageFileInfo(Damage $damage, array $upload, Folder $destinationFolder, Folder $ticketsFolder, string $subType): DamageImage
    {
        $damageImage = new DamageImage();
        $this->containerUtility->convertRequestKeysToSetters([
            'damage' => $damage,
            'name' => $upload['originalName'],
            'displayName' => $upload['fileDisplayName'],
            'isEditable' => false,
            'path' => $destinationFolder->getPath() . '/' . $upload['originalName'],
            'imageCategory' => $subType,
            'mimeType' => isset($upload['mimeType']) ? $upload['mimeType'] : $upload['type'],
            'fileSize' => $upload['size'],
            'folder' => $ticketsFolder
        ], $damageImage);

        return $damageImage;
    }

    /**
     * @param Damage $damage
     * @param UserIdentity $user
     * @return array
     * @throws \Exception
     */
    public function getTicketDocFolder(Damage $damage, UserIdentity $user): array
    {
        $objectFolder = $damage->getApartment()->getFolder();
        $ticketsFolder = $this->em->getRepository(Folder::class)->findOneBy(['name' => Constants::DAMAGE_DOC_FOLDER, 'parent' => $objectFolder]);
        if (is_null($ticketsFolder)) {
            $this->dmsService->createFolder(Constants::DAMAGE_DOC_FOLDER, $user, true, $objectFolder->getPublicId(), false, true);
            $ticketsFolder = $this->em->getRepository(Folder::class)->findOneBy(['name' => Constants::DAMAGE_DOC_FOLDER, 'parent' => $objectFolder]);
        }
        if ($damage->getFolder() === null) {
            $newFolder = $this->dmsService->createFolder(Constants::DAMAGE_NAME_PREFIX . $damage->getId(), $user, true, $ticketsFolder->getPublicId(), false);
            $destinationFolder = $this->em->getRepository(Folder::class)->findOneBy(['publicId' => $newFolder[0]['publicId']]);
            $damage->setFolder($destinationFolder);
            $this->em->persist($damage);
        } else {
            $destinationFolder = $damage->getFolder();
        }

        return ['destinationFolder' => $destinationFolder, 'ticketsFolder' => $ticketsFolder];
    }

    /**
     * @param DamageImage|null $fileInfo
     * @param string $baseUrl
     * @param bool|null $encode
     * @return array
     * @throws \Exception
     */
    public function getDamageFileInfo(?DamageImage $fileInfo, string $baseUrl, ?bool $encode = true): array
    {
        $data = [];
        if ($fileInfo instanceof DamageImage) {
            $data['publicId'] = $fileInfo->getPublicId();
            $data['originalName'] = $fileInfo->getName();
            $data['path'] = $baseUrl . str_replace($this->params->get('damage_path'), '/', $fileInfo->getPath());
            $data['displayName'] = $this->dmsService->removeFileExtension($fileInfo->getDisplayName());
            $data['filePath'] = $fileInfo->getPath();
            $data['isPrivate'] = 'public';
            $data['mimeType'] = $fileInfo->getMimeType();
            $data['size'] = $fileInfo->getFileSize();
            $data['folder'] = $fileInfo->getFolder()->getPublicId();
            $data['updatedAt'] = $fileInfo->getUpdatedAt();
            $data['type'] = array_search($fileInfo->getImageCategory(), $this->params->get('image_category'));
//            if ($encode) {
//                $data['encodedData'] = $this->dmsService->getDocumentEncodedData($fileInfo->getPath());
//            }
            if (str_starts_with($fileInfo->getMimeType(), 'image')) {
                $data['thumbnails'] = $this->dmsService->getThumbnails(pathinfo($fileInfo->getPath(), PATHINFO_BASENAME), $data['path']);
            }

            if (in_array($data['mimeType'], Constants::EXCEPTED_MIME_TYPES)) {
                if ($encode) {
                    $params = ['path' => $fileInfo->getPath(), 'mimeType' => $data['mimeType']];
                    $data['encodedData'] = $this->dmsService->getDocumentEncodedData($params);
                }
//                $data['path'] = $this->getDocumentViewUrl(str_replace($this->params->get('root_directory'), '/', $fileInfo->getStoredPath()), $baseUrl, $fileInfo->getMimeType());
                $relativePath = $fileInfo->getPath();
                $len = strrpos($relativePath, "/files/");
                $data['path'] = $this->dmsService->getDocumentViewUrl(substr($fileInfo->getPath(), $len), $baseUrl, $fileInfo->getMimeType());
            } elseif ($encode) {
                $params = ['path' => $fileInfo->getPath(), 'mimeType' => $data['mimeType']];
                $data['encodedData'] = $this->dmsService->getDocumentEncodedData($params);
            }
        }

        return $data;
    }

    /**
     *
     * @param Document $fileInfo
     * @return void
     */
    public function checkReadAccess(Document $fileInfo): void
    {
        if (!$fileInfo->getIsPrivate()) {
            $fileInfo->getFolder()->setIsPrivate(false);

            $parents = $this->em->getRepository(Folder::class)->findParent($fileInfo->getFolder());
            foreach ($parents as $parent) {
                $folder = $this->em->getRepository(Folder::class)->findOneByPublicId($parent);
                $folder->setIsPrivate(false);
            }
        }

        return;
    }

}
