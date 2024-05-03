<?php

/**
 * This file is part of the Amoulet Project package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Service\ImageOptimizerService;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use PhpZip\ZipFile;
use Symfony\Component\HttpFoundation\File\File;

/**
 * StoryServiceManager
 *
 * Class to handle Story service functions
 *
 * @package         Amoulet
 * @subpackage      App
 * @author          Rahul
 */
class FileUploaderUtility
{
    /**
     * @var ParameterBagInterface $params
     */
    private ParameterBagInterface $params;

    /**
     * @var ContainerUtility $containerUtility
     */
    private ContainerUtility $containerUtility;

    /**
     * @var Security $security
     */
    private Security $security;

    /**
     * @var ManagerRegistry $doctrine
     */
    private ManagerRegistry $doctrine;

    /**
     * @var ImageOptimizerService $imageOptimizerService
     */
    private ImageOptimizerService $imageOptimizerService;

    /**
     * @var KernelInterface $appKernel
     */
    private KernelInterface $appKernel;

    /**
     * Constructor
     *
     * @param ParameterBagInterface $params
     * @param ContainerUtility $containerUtility
     * @param Security $security
     * @param ManagerRegistry $doctrine
     * @param ImageOptimizerService $imageOptimizerService
     * @param KernelInterface $appKernel
     */
    public function __construct(ParameterBagInterface $params, ContainerUtility $containerUtility, Security $security, ManagerRegistry $doctrine, ImageOptimizerService $imageOptimizerService, KernelInterface $appKernel)
    {
        $this->params = $params;
        $this->containerUtility = $containerUtility;
        $this->security = $security;
        $this->doctrine = $doctrine;
        $this->imageOptimizerService = $imageOptimizerService;
        $this->appKernel = $appKernel;
    }

    /**
     * Upload file
     *
     * @param UploadedFile $file
     * @param string $path
     * @param string $key
     * @param bool $isTemp
     * @param string|null $originalFileName
     * @return array
     * @throws \Exception
     */
    public function upload(UploadedFile $file, string $path, string $key, bool $isTemp = true, ?string $originalFileName = null): array
    {
        $this->validateFile($file, $key);
        if ($isTemp) {
            $path .= "/$key/";
            $filePath = $this->params->get('temp_upload_path') . "/$key/";
        } else {
            if ($key == 'ticket') {
                $filePath = $this->params->get('property_path') . '/' . $this->getFolderPath($path) . "/";
                $path .= "/";
            } else {
                $filePath = $this->params->get('property_path') . '/' . $this->getFolderPath($path) . "/$key/";
                $path .= "/$key/";
            }
        }
        if (is_null($originalFileName) || empty($originalFileName)) {
            $originalFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        }
        $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
        $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFileName);
        $fileName = $safeFilename . '-' . uniqid(strtotime('now')) . '.' . $extension;
        $fileSize = $file->getSize();
        $filesystem = new Filesystem();
        if (!$filesystem->exists($path)) {
            $filesystem->mkdir($path, 0755);
        }
        if (!$file->move($path, $fileName)) {
            throw new FileException('fileUploadFailed');
        }
        $filePath .= $fileName;
        $type = $file->getClientMimeType();
        //this->logFileInfo($file, $fileName, $path, $key, $fileSize);
        return ['originalName' => $fileName, 'path' => $path . $fileName, 'fileDisplayName' => $originalFileName . '.' . $extension,
            'type' => $type, 'size' => $fileSize, 'filePath' => $filePath];
    }

    /**
     * @param File $file
     * @param string $path
     * @param string $key
     * @param bool $isTemp
     * @param string|null $originalFileName
     * @return array
     */
    public function uploadJpgFile(File $file, string $path, string $key, bool $isTemp = true, ?string $originalFileName = null): array
    {
        $this->validateJpgFile($file, $key);
        if ($isTemp) {
            $path .= "/$key/";
            $filePath = $this->params->get('temp_upload_path') . "/$key/";
        } else {
            if ($key == 'ticket') {
                $filePath = $this->params->get('property_path') . '/' . $this->getFolderPath($path) . "/";
                $path .= "/";
            } else {
                $filePath = $this->params->get('property_path') . '/' . $this->getFolderPath($path) . "/$key/";
                $path .= "/$key/";
            }
        }
        if (is_null($originalFileName) || empty($originalFileName)) {
            $originalFileName = pathinfo($file->getBasename(), PATHINFO_FILENAME);
        }
        $extension = pathinfo($file->getBasename(), PATHINFO_EXTENSION);
        $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFileName);
        $fileName = $safeFilename . '-' . uniqid(strtotime('now')) . '.' . $extension;
        $fileSize = $file->getSize();
        $filesystem = new Filesystem();
        $type = $file->getMimeType();
        if (!$filesystem->exists($path)) {
            $filesystem->mkdir($path, 0755);
        }
        if (!$file->move($path, $fileName)) {
            throw new FileException('fileUploadFailed');
        }
        $filePath .= $fileName;
        return ['originalName' => $fileName, 'path' => $path . $fileName, 'fileDisplayName' => $fileName,
            'type' => $type, 'size' => $fileSize, 'filePath' => $filePath];
    }

    /**
     * Log media information
     *
     * @param UploadedFile $file
     * @param string $fileName
     * @param string $path
     * @param string $key
     * @param int $fileSize
     * @return type
     * @throws \Exception
     */
    public function logFileInfo(UploadedFile $file, string $fileName, string $path, string $key, int $fileSize)
    {
        $mediaLog = new MediaLog();
        $values = array('type' => $file->getClientMimeType(), 'createdAt' => new \DateTime(), 'deleted' => 0, 'path' => $path . '/' . $fileName, 'size' => $fileSize, 'mediaType' => $key);
        return $this->containerUtility->convertRequestKeysToSetters($values, $mediaLog);
    }

    /**
     * Removes file
     * @param string $filePath
     * @return void
     */
    public function removeFile(string $filePath): void
    {
        $fileToRemove = $this->params->get('public_directory') . $filePath;
        $filesystem = new Filesystem();
        if ($filesystem->exists($fileToRemove)) {
            $filesystem->remove($fileToRemove);
        }
    }

    /**
     *  Function for file Upload validation
     *
     * @param UploadedFile $file
     * @param string $key
     * @return void
     * @throws
     */
    public function validateFile(UploadedFile $file, string $key): void
    {
        $fileType = $file->getClientMimeType();
        $fileSize = $file->getSize();
        $apartmentDocumentTypes = ['application/x-pdf', 'application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        $propertyTypes = ['application/x-pdf', 'image/jpeg', 'image/jpg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'image/gif', 'image/tiff'];
        $damageTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        $maxFileSize = $this->params->get('file_size_max');
        if ($key === 'apartmentDocument' || $key === 'apartment') {
            if (!in_array($fileType, $apartmentDocumentTypes)) {
                throw new \Exception('invalidFileType');
            }
        }
        if ($key === 'propertyDocument' || $key === 'property') {
            if (!in_array($fileType, $propertyTypes)) {
                throw new \Exception('invalidFileType');
            }
        }
        if ($key === 'damageDocument') {
            if (!in_array($fileType, $damageTypes)) {
                throw new \Exception('invalidFileType');
            }
        }
        if ($fileSize > $maxFileSize) {
            throw new \Exception('invalidFileSize');
        }
    }

    /**
     *  Function for file validation after conversion
     *
     * @param File $file
     * @param string $key
     * @return void
     * @throws \Exception
     */
    public function validateJpgFile(File $file, string $key): void
    {
        $fileType = $file->getMimeType();
        $fileSize = $file->getSize();
        $apartmentDocumentTypes = ['application/x-pdf', 'application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        $propertyTypes = ['application/x-pdf', 'image/jpeg', 'image/jpg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'image/gif', 'image/tiff'];
        $damageTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        $maxFileSize = $this->params->get('file_size_max');
        if ($key === 'apartmentDocument' || $key === 'apartment') {
            if (!in_array($fileType, $apartmentDocumentTypes)) {
                throw new \Exception('invalidFileType');
            }
        }
        if ($key === 'propertyDocument' || $key === 'property') {
            if (!in_array($fileType, $propertyTypes)) {
                throw new \Exception('invalidFileType');
            }
        }
        if ($key === 'damageDocument') {
            if (!in_array($fileType, $damageTypes)) {
                throw new \Exception('invalidFileType');
            }
        }
        if ($fileSize > $maxFileSize) {
            throw new \Exception('invalidFileSize');
        }
    }

    /**
     * Save file names against users
     *
     * @param UserDetail $userDetail
     * @param array $files
     * @param Request $request
     * @return array
     */
    public function saveAndReturnFileName(UserDetail $userDetail, array $files, Request $request): array
    {
        $resources = [];
        foreach ($files as $path => $fileNames) {
            list($folder, $resource) = explode('/', $path);
            $resources[$resource][$path] = $fileNames;
        }
        return $this->saveResourceFilesAndReturn($userDetail, $resources, $request);
    }

    /**
     * Update file names against users
     *
     * @param UserDetail $userDetail
     * @param array $files
     * @param Request $request
     * @return array
     */
    public function updateAndReturnFileName(UserDetail $userDetail, array $files, Request $request): array
    {
        $resources = [];
        $em = $this->doctrine->getManager();
        foreach ($userDetail->getCertificate() as $certificate) {
            $em->remove($certificate);
            $this->removeFile($certificate->getUrl());
        }
        foreach ($userDetail->getReference() as $reference) {
            $em->remove($reference);
            $this->removeFile($reference->getUrl());
        }
        foreach ($files as $path => $fileNames) {
            list($uploadDirecotry, $folder, $resource) = explode('/', $path);
            $resources[$resource][$path] = $fileNames;
        }
        return $this->saveResourceFilesAndReturn($userDetail, $resources, $request);
    }

    /**
     * Save resource file names
     *
     * @param UserDetail $userDetail
     * @param array $resources
     * @param Request $request
     * @return array
     */
    private function saveResourceFilesAndReturn(UserDetail $userDetail, array $resources, Request $request): array
    {
        $data = [];
        $em = $this->doctrine->getManager();
        $baseurl = $this->getBaseUrl($request);
        foreach ($resources as $resource => $files) {
            foreach ($files as $path => $file) {
                if ($resource !== 'ProfileImage') {
                    $filePath = $path;
                    $className = "App\Entity\\$resource";
                    if (class_exists($className)) {
                        foreach ($file as $filename) {
                            $reference = new $className();
                            $reference->setUserDetail($userDetail);
                            if ($reference->hasProperty('url')) {
                                $path = $baseurl . "/$filePath/$filename";
                                $reference->setUrl($path);
                            }
                            $em->persist($reference);
                            $data[$resource][] = $reference;
                        }
                    }
                    unset($filePath);
                } else {
                    $data[$resource] = $baseurl . "/$path/$file";
                }
            }
        }
        $em->flush();
        return $data;
    }

    /**
     *
     * @param string $objectReference
     * @param User $user
     * @return void
     */
    public function removeUserDocuments(string $objectReference, User $user): void
    {
        $em = $this->doctrine->getManager();
        if ($objectReference === 'UserCertificate') {
            foreach ($user->getUserDetail()->getCertificate() as $certificate) {
                $em->remove($certificate);
                $this->removeFile($certificate->getUrl());
            }
        } else if ($objectReference === 'UserReference') {
            foreach ($user->getUserDetail()->getReference() as $reference) {
                $em->remove($reference);
                $this->removeFile($reference->getUrl());
            }
        }
    }

    /**
     *  Function to resize uploaded profile image to different sizes
     *
     * @param string $fileName
     * @param string $path
     * @return array
     */
    public function resizeUploadedImageToDifferentVariants(string $fileName, string $path): array
    {
        $resizedFile = [];
        $imageDimensions = $this->params->get('image_sizes');
        foreach ($imageDimensions as $key => $imageDimension) {
            if ($key == 0) continue;
            $dimension = explode('*', $imageDimension);
            list($width, $height) = $dimension;
            $newFileName = $path . '/' . $width . '-' . $height . '-' . basename($fileName);
            $resizedFile[$key] = $newFileName;
            $filter = 'thumbnail'; // Name of the `filter_set` in `config/packages/liip_imagine.yaml`
            $this->imageOptimizerService->resizeAndStoreImage($fileName, $newFileName, $filter, $width, $height);
        }
        return $resizedFile;
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
     * Function to optimize uploaded files
     *
     * @param string $file
     * @param string $mimeType
     * @return void
     * @throws \PhpZip\Exception\ZipException
     */
    public function optimizeFile(string $file, string $mimeType): void
    {
        $pathParts = pathinfo($file);
        $path = explode('/' . $this->params->get('files_folder') . '/', $pathParts['dirname'])[1];
        $source = '/' . $path . '/' . $pathParts['basename'];
        $destination = $this->appKernel->getProjectDir() . '/' . $this->params->get('files_folder') . '/' . $path;
        if (str_starts_with(trim($mimeType), 'image')) {
            $this->resizeUploadedImageToDifferentVariants($source, $destination);
        } else {
            $this->compressFile($pathParts['basename'], $pathParts['filename'], $destination);
        }
    }

    /**
     * Function to compress file
     *
     * @param string $basename
     * @param string $filename
     * @param string $destinationPath
     * @return void
     * @throws \PhpZip\Exception\ZipException
     */
    public function compressFile(string $basename, string $filename, string $destinationPath): void
    {
        $file = $destinationPath . '/' . $basename;
        $zipFile = new ZipFile();
        $zipFile
            ->addFile($file)
            ->saveAsFile($destinationPath . '/' . $filename . '.zip')
            ->close();
        if (is_file($file)) {
            unlink($file);
        }
    }

    /**
     * Function to decompress file
     *
     * @param string $compressedFilePath
     * @return string
     * @throws \PhpZip\Exception\ZipException
     */
    public function decompressFile(string $compressedFilePath): string
    {
        if (is_file($compressedFilePath)) {
            $pathParts = pathinfo($compressedFilePath);
            $zipFile = new ZipFile();
            $zipFile
                ->openFile($compressedFilePath)
                ->extractTo($pathParts['dirname']);
        }

        return $compressedFilePath;
    }

    /**
     * Function to encode a document to base64
     *
     * @param array $params
     * @return array
     */
    public function fileToBase64(array $params): array
    {
        $path = $params['path'];
        if (!file_exists($path)) {
            throw new FileNotFoundException('fileNotExists');
        }
        $info = getimagesize($path);
        list($width, $height) = $info;
        $buffer = file_get_contents($path);
        $fInfo = new \finfo(FILEINFO_MIME_TYPE);
        $document = base64_encode($buffer);
        $encodedData = ['mimeType' => $fInfo->buffer($buffer), 'document' => $document, 'dimensions' => ['width' => $width, 'height' => $height]];
        if (!empty($encodedData)) {
            return $encodedData;
        }
        throw new FileNotFoundException('encodeFail');

//        $path = $params['path'];
//        if (!file_exists($path)) {
//            throw new FileNotFoundException('fileNotExists');
//        }
//        $imageFile = fopen($path, 'rb');
//        $base64String = '';
//        while (!feof($imageFile)) {
//            $chunk = fread($imageFile, 102400); // Read 100 kilobytes at a time
//            $base64String .= base64_encode($chunk);
//        }
//        fclose($imageFile);
//        $encodedData = ['mimeType' => $params['mimeType'], 'document' => $base64String];
//        if(!empty($encodedData)){
//            return $encodedData;
//        }
//        throw new FileNotFoundException('encodeFail');
    }

    /**
     *
     * @param string $imageDataEncoded
     * @param string $fileName
     * @param string $path
     * @param string $type
     * @param bool|null $isTemp
     * @return array
     */
    public function base64ToFile(string $imageDataEncoded, string $fileName, string $path, string $type, ?bool $isTemp = false): array
    {
        if ($isTemp) {
            $filePath = $this->params->get('temp_upload_path') . "/$type/";
            $path .= "/$type/";
        } else {
            $filePath = $this->params->get('property_path') . '/' . $this->getFolderPath($path) . '/';
            $path .= "/";
        }
        $imageData = explode(',', $imageDataEncoded);
        $source = imagecreatefromstring(base64_decode($imageData[1]));
        $filesystem = new Filesystem();
        if (!$filesystem->exists($path)) {
            $filesystem->mkdir($path, 0755);
        }
        $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $fileName);
        $newFileName = $safeFilename . '-' . uniqid(strtotime('now')) . '.jpeg'; // always to jpeg
        $path .= $newFileName;
        $filePath .= $newFileName;
        imagejpeg($source, $path, 100);
        imagedestroy($source);

        return ['originalName' => $newFileName, 'path' => $path, 'fileDisplayName' => $fileName,
            'type' => $type, 'size' => filesize($path), 'filePath' => $filePath, 'mimeType' => 'image/jpeg'];
    }
}
