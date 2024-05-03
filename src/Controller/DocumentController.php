<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Folder;
use App\Entity\Interfaces\ReturnableDocumentInterface;
use App\Entity\TemporaryUpload;
use App\Utils\Constants;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use App\Helpers\FileUploadHelper;
use App\Utils\ValidationUtility;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\String\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Exception\BadMethodCallException;
use App\Utils\GeneralUtility;
use FOS\RestBundle\View\View;
use App\Service\DMSService;
use App\Utils\FileUploaderUtility;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Psr\Log\LoggerInterface;

/**
 * DocumentController
 *
 * Controller to manage general functions
 *
 * @package         BaluProperty
 * @subpackage      App
 * @author          pitsolutions.ch
 * @Route("/document")
 */
final class DocumentController extends BaseController
{
    /**
     * API end point to upload documents to folders.
     *
     * # Request
     * In request body, system expects documents as a form type request and not JSON.
     * ### Example request to upload property document, expects files[], type as property, folder, fileName, permission, property, isEncode
     * ### Example request to apartment document expects files[], folder, fileName, permission and type as apartment, apartment, isEncode
     * ### Example request to contract document expects files[], folder, fileName, permission and type as contract, contract, isEncode
     * ### Example request to apartment floorPlan expects files[], folder, fileName, permission and type as floorPlan, apartment, isEncode
     * ### Example request to ticket image expects files[], folder(ticket id), fileName, permission, type as ticket and subType (photos, floorPlan, barCode, defect, confirm)
     * ### Example request to property/apartment cover image expects files[], folder, fileName, permission, type as coverImage, property, isEncode
     *
     * ### Route /api/2.0/document/upload
     *
     * # Response
     * ## Success response ##
     *      {
     *          "data": [
     *              {
     *                  "identifier": 14,
     *                  "public_id": "1ecd1a8a-5a5a-6740-b87e-0242ac120003",
     *                  "created_at": "2022-05-12T04:04:40+00:00",
     *                  "deleted": false,
     *                  "original_file_name": "applsci-11-10633-v2.pdf",
     *                  "file_size": 874240.0,
     *                  "temporary_upload_path": "/var/www/app/files/documents_temp/propertyDocument/applsci1110633v2-1652328280627c8758167e6.pdf",
     *                  "mime_type": "application/pdf",
     *                  "doc_type": "propertyDocument",
     *                  "file_path": "http://localhost:8001/files/documents_temp/propertyDocument/applsci1110633v2-1652328280627c8758167e6.pdf",
     *                  "local_file_name": "applsci1110633v2-1652328280627c8758167e6.pdf"
     *              }
     *          ],
     *          "error": false,
     *          "message": "uploadSuccess"
     *      }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Upload failed"
     *       }
     * @Route("/upload", name="balu_document_upload", methods={"POST"})
     * @Operation(
     *      tags={"Document"},
     *      summary="API end point to upload documents.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *              @OA\Property(property="files[]", type="file", default="", example="uploads/ProfileImage/610b6a008ec44/screenshot4-610b6a008ef0a.png"),
     *              @OA\Property(property="fileName", type="string", default="myfile", example="myfile"),
     *              @OA\Property(property="type", type="string", default="property", example="property"),
     *              @OA\Property(property="subType", type="string", default="default", example="barCode"),
     *              @OA\Property(property="permission", type="boolean", default="public", example="public"),
     *              @OA\Property(property="folder", type="string", default="1ecd1a8a-5a5a-6740-b87e-0242ac120003", example="1ecd1a8a-5a5a-6740-b87e-0242ac120003"),
     *              @OA\Property(property="isEncode", type="string")
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
     * @param FileUploadHelper $fileUploadHelper
     * @param GeneralUtility $generalUtility
     * @param ValidationUtility $validationUtility
     * @param LoggerInterface $requestLogger
     * @param string|null $publicId
     * @return View
     * @throws \Exception
     */
    public function upload(Request $request, FileUploadHelper $fileUploadHelper, GeneralUtility $generalUtility,
                           ValidationUtility $validationUtility, LoggerInterface $requestLogger, ?string $publicId = null): View
    {
        $curDate = new \DateTime('now');
        $files = array_merge($request->files->all());
        $request->request->set('files[]', $files);
        $aViolations = $validationUtility->validateData('documentUpload', $request->request->all());
        if ($aViolations) {
            $data = $generalUtility->handleFailedResponse(array_values($aViolations)[0][0], 400,
                $generalUtility->formatErrors($aViolations));
            return $this->response($data);
        }
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            $aFiles = $request->files->all();
            $aData = $request->request->all();
            if (!is_null($publicId)) {
                $aData['publicId'] = $publicId;
            }
            $aData['isEncode'] = $aData['isEncode'] ?? false;
            $aData['isEncode'] = filter_var($aData['isEncode'], FILTER_VALIDATE_BOOLEAN);
            $fileNames = $fileUploadHelper->uploadDocument($this->getUser(), $request->getSchemeAndHttpHost(), $aFiles, $aData);
            $em->flush();
            $em->commit();
            $data = $generalUtility->handleSuccessResponse('uploadSuccess', $fileNames);
        } catch (EntityNotFoundException | FileException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $em->rollback();
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }
        return $this->response($data);
    }

    /**
     * API end point to search documents.
     *
     * ### Route /api/2.0/document/search
     * # Request
     * ## Example request to create new property
     *
     *      {
     *          "search": "param",
     *          "folder": "1ed39944-d62a-67b4-a1d3-00155d01d845"
     *      }
     *
     * # Response
     * ## Success response ##
     *      {
     *       "data": {
     *           "folders": [],
     *           "propertyDocuments": [
     *               {
     *                   "publicId": {
     *                       "uid": "1ec7c336-b774-6c24-914f-0242ac120004"
     *                   },
     *                   "fileDisplayName": "reprt.jpg",
     *                   "path": "/var/www/html/app/files/property/p28/documents/reprt-61ed27720f8b4.jpg"
     *               }
     *           ],
     *           "apartmentDocuments": [
     *               {
     *                   "publicId": {
     *                       "uid": "1ec83fa1-42da-625a-89c9-0242ac120004"
     *                   },
     *                   "fileDisplayName": "reprt.jpg",
     *                   "path": "/var/www/html/app/files/property//apartment/documents/reprt-61fa333762b91.jpg"
     *               }
     *           ]
     *       },
     *       "error": false,
     *       "message": "searchSuccess"
     *   }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Search failed"
     *       }
     * @Route("/search", name="balu_search_document", methods={"POST"})
     * @Operation(
     *      tags={"Document"},
     *      summary="API end point to search documents.",
     *      @Security(name="Bearer"),
     *      @OA\Parameter(
     *       name="name",
     *       in="query",
     *       description="File name to be searched ",
     *       required=true,
     *       @OA\Schema(
     *         type="string",
     *       ),
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
     * @param DMSService $dmsService
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function search(Request $request, GeneralUtility $generalUtility, DMSService $dmsService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        try {
            $role = $dmsService->convertSnakeCaseString($this->currentRole);
            $method = $role . 'Folder';
            $result = $dmsService->$method($this->getUser()->getIdentifier(), null, $request->request->all());
            $data = $generalUtility->handleSuccessResponse('searchSuccess', $result);
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse('searchFail');
        }
        return $this->response($data);
    }

    /**
     * API end point to create new property.
     *
     * ### Route /api/2.0/document/create-folder
     * # Request
     * In request body, system expects folder details as JSON.
     * ## Example request to create new property
     *
     *      {
     *          "name": "folder1",
     *          "isPrivate": private,
     *          "parent": "1ec85984-3289-6750-8b8a-0242ac120004",
     *          "isManual": true
     *      }
     * # Response
     * ## Success response ##
     *       {
     *           "data": {
     *               "identifier": 46,
     *               "public_id": "1ec85984-3289-6750-8b8a-0242ac120004",
     *               "created_at": "2022-02-04T08:55:54+00:00",
     *               "deleted": false,
     *               "name": "folder1",
     *               "path": "/var/www/html/app/files/property/folder1",
     *               "is_private": true,
     *               "created_by": {},
     *           },
     *           "error": false,
     *           "message": "folderSuccess"
     *   }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "folderCreateFail"
     *       }
     * @Route("/create-folder", name="balu_create_folder", methods={"POST"})
     * @Operation(
     *      tags={"Document"},
     *      summary="API end point to create new property.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="parent", type="string", default="", example="uuid"),
     *               @OA\Property(property="name", type="string", default="", example="Hellen"),
     *               @OA\Property(property="isPrivate", type="string", default="", example="private"),
     *               @OA\Property(property="isManual", type="boolean", default="", example=true)
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
     * @param GeneralUtility $generalUtility
     * @param DMSService $dmsService
     * @param ValidationUtility $validationUtility
     * @param Request $request
     * @return View
     * @throws \Exception
     */
    public function createFolder(GeneralUtility $generalUtility, DMSService $dmsService, ValidationUtility $validationUtility, Request $request, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $isManual = $request->request->get('isManual') ? true : false;
        $request->request->set('isManual', $isManual);
        $aViolations = $validationUtility->validateData('folder', $request->request->all());
        if ($aViolations) {
            $data = $generalUtility->handleFailedResponse('mandatoryFieldsMissing', 400, $generalUtility->formatErrors($aViolations));
            return $this->response($data);
        }
        try {
            $isSystemGenerated = $request->request->get('isManual') === true ? false : true;
            $isPrivate = $request->request->get('isPrivate') === 'private';
            $path = $dmsService->createFolder($request->request->get('name'), $this->getUser(), $isSystemGenerated,
                $request->request->get('parent'), $isPrivate);
            $data = $generalUtility->handleSuccessResponse('folderSuccess', $path);
        } catch (\Exception | InvalidArgumentException $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point list property folders.
     *
     * # Response
     * ## Success response ##
     *       {
     *           "data": {
     *               "publicId": "1ec85984-3289-6750-8b8a-0242ac120004",
     *               "created_at": "2022-02-04T08:55:54+00:00",
     *               "deleted": false,
     *               "name": "folder1",
     *               "path": "/var/www/html/app/files/property/folder1",
     *               "is_private": true,
     *               "isSystemGenerated": true
     *           },
     *           "error": false,
     *           "message": "folderSuccess"
     *      }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "folderCreateFail"
     *       }
     * @Route("/list/{parent}", name="balu_list_folder", methods={"GET"})
     * @OA\Tag(name="Document")
     * @OA\Response(
     *         response="200",
     *         description="Returned on successful response"
     *  ),
     * @OA\Response(
     *         response="400",
     *         description="Returned when request is not valid"
     * ),
     * @OA\Response(
     *          response="401",
     *          description="User not authenticated"
     * ),
     * @OA\Response(
     *         response="500",
     *         description="Internal Error"
     *  )
     *)
     * @param Request $request
     * @param DMSService $dmsService
     * @param GeneralUtility $generalUtility
     * @param ValidationUtility $validationUtility
     * @param string|null $parent
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function list(Request $request, DMSService $dmsService, GeneralUtility $generalUtility,
                         ValidationUtility $validationUtility, LoggerInterface $requestLogger, ?string $parent = null): View
    {
        $curDate = new \DateTime('now');
        $currentUserRole = $this->currentRole;
        $role = $dmsService->convertSnakeCaseString($currentUserRole);
        $method = $role . 'Folder';
        try {
            $queryParams = $request->query->all();
            if (!method_exists($dmsService, $method)) {
                throw new BadMethodCallException("methodNotFound");
            }
            if (!is_null($parent) && !$validationUtility->checkIfUuidValid('Folder', $parent)) {
                throw new EntityNotFoundException('folderFetchError');
            }
            $result = $dmsService->$method($this->getUser()->getIdentifier(), $parent);
            if (!is_null($parent)) {
                $result['isPrivate'] = $dmsService->getAccessibility($parent);
                $result['parents'] = $dmsService->getParentFolders($parent, $this->locale);
            }
            if (!empty($queryParams)) {
                $result['params'] = $queryParams;
                $result['property'] = $queryParams['property'] ?? null;
                $result['type'] = $queryParams['type'] ?? null;
            }
            $data = $generalUtility->handleSuccessResponse('folderFetchSuccess', $result);
        } catch (BadMethodCallException | FileNotFoundException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }
        return $this->response($data);
    }

    /**
     * API end point to edit folder.
     *
     * # Request
     * In request body, system expects folder details as JSON.
     * ## Example request to create new property
     *
     *      {
     *          "name": "folder1",
     *          "isPrivate": public/private
     *      }
     * # Response
     * ## Success response ##
     *       {
     *           "data": {
     *               "publicId": "1ec85984-3289-6750-8b8a-0242ac120004",
     *               "created_at": "2022-02-04T08:55:54+00:00",
     *               "deleted": false,
     *               "name": "folder1",
     *               "path": "/var/www/html/app/files/property/folder1",
     *               "is_private": true,
     *               "isSystemGenerated": true
     *           },
     *           "error": false,
     *           "message": "folderSuccess"
     *      }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "folderEditFail"
     *       }
     * @Route("/edit-folder/{uuid}", name="balu_edit_folder", methods={"PUT"})
     * @OA\Tag(name="Document")
     * @OA\Response(
     *         response="200",
     *         description="Returned on successful response"
     *  ),
     * @OA\Response(
     *         response="400",
     *         description="Returned when request is not valid"
     * ),
     * @OA\Response(
     *          response="401",
     *          description="User not authenticated"
     * ),
     * @OA\Response(
     *         response="500",
     *         description="Internal Error"
     *  )
     *)
     * @param Request $request
     * @param string $uuid
     * @param GeneralUtility $generalUtility
     * @param ValidationUtility $validationUtility
     * @return View
     */
    public function editFolder(Request $request, string $uuid, GeneralUtility $generalUtility, ValidationUtility $validationUtility, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        try {
            if (!$validationUtility->checkIfUuidValid('folder', $uuid)) {
                throw new InvalidArgumentException('invalidString');
            }
//            $dmsService->checkIsEditPermissionGranted($request$uuid, $this->getUser());
            $folder = $em->getRepository(Folder::class)->findOneBy(['publicId' => $uuid]);
            if ($folder instanceof Folder) {
                $folder->setDisplayName($request->request->get('name'));
                $isPrivate = $request->request->get('isPrivate') && $request->request->get('isPrivate') === 'private';
                $folder->setIsPrivate($isPrivate);
                $em->flush();
            }
            $response = $em->getRepository(Folder::class)->getFolderInfo($folder);
            $data = $generalUtility->handleSuccessResponse('folderEditSuccess', $response);
        } catch (\Exception  $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to edit document.
     *
     * # Request
     * In request body, system expects folder details as JSON.
     * ## Example request to create new property
     *
     *      {
     *          "name": "folder1",
     *          "isPrivate": public/private,
     *          "type": "property/apartment"
     *      }
     * # Response
     * ## Success response ##
     *       {
     *           "data": {
     *               "publicId": "1ec85984-3289-6750-8b8a-0242ac120004",
     *               "created_at": "2022-02-04T08:55:54+00:00",
     *               "deleted": false,
     *               "name": "folder1",
     *               "path": "/var/www/html/app/files/property/folder1",
     *               "is_private": true,
     *               "isSystemGenerated": true
     *           },
     *           "error": false,
     *           "message": "documentEditSuccess"
     *      }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "documentEditFail"
     *       }
     * @Route("/edit-document/{uuid}", name="balu_edit_document", methods={"PUT"})
     * @OA\Tag(name="Document")
     * @OA\Response(
     *         response="200",
     *         description="Returned on successful response"
     *  ),
     * @OA\Response(
     *         response="400",
     *         description="Returned when request is not valid"
     * ),
     * @OA\Response(
     *          response="401",
     *          description="User not authenticated"
     * ),
     * @OA\Response(
     *         response="500",
     *         description="Internal Error"
     *  )
     *)
     * @param Request $request
     * @param string $uuid
     * @param GeneralUtility $generalUtility
     * @param ValidationUtility $validationUtility
     * @param DMSService $dmsService
     * @return View
     */
    public function editDocument(Request $request, string $uuid, GeneralUtility $generalUtility,
                                 ValidationUtility $validationUtility, DMSService $dmsService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        try {
            $type = $request->request->get('type');
            if (!in_array($type, Constants::DOC_TYPES)) {
                throw new InvalidArgumentException('invalidDocType');
            }
            // need to handle doctype apartment and tenant
            if (!$validationUtility->checkIfUuidValid('Document', $uuid)) {
                throw new InvalidArgumentException('invalidString');
            }
            $document = $em->getRepository(Document::class)->findOneBy(['publicId' => $uuid]);
            if ($document instanceof Document) {
                $name = $request->request->get('name') . '.' . Constants::ALLOWED_DOC_TYPES[$document->getMimeType()];
                $document->setDisplayName($name);
                $isPrivate = $request->request->has('isPrivate') && $request->request->get('isPrivate') === 'private';
                $document->setIsPrivate($isPrivate);
                $em->flush();
            }
            $response = $dmsService->getUploadInfo($document, $request->getSchemeAndHttpHost(), false);
            $data = $generalUtility->handleSuccessResponse('documentEditSuccess', $response);
        } catch (\Exception  $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to delete a document/folder.
     *
     * # Request
     * In request body, system expects folder details as JSON.
     * ## Example request to create new property
     * ### api/2.0/document/delete/{type}/{uuid} where type refers to the type of document. Possible values are temporary, document and folder
     * #### temporary => Temporary upload -> before creating an object
     * #### document => Uploaded document -> After creation of a document
     * #### folder => Folder -> After folder creation
     * #### message => Message document -> After creation of a Message document
     * #### ticket => Ticket document -> After creation of a Ticket document
     *
     * # Response
     * ## Success response ##
     *      {
     *          "data": "No data provided",
     *          "error": false,
     *          "message": "documentDeleteSuccess"
     *      }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "documentEditFail"
     *       }
     * @Route("/delete/{type}/{uuid}", name="balu_delete_document", methods={"DELETE"})
     * @OA\Tag(name="Document")
     * @OA\Response(
     *         response="200",
     *         description="Returned on successful response"
     *  ),
     * @OA\Response(
     *         response="400",
     *         description="Returned when request is not valid"
     * ),
     * @OA\Response(
     *          response="401",
     *          description="User not authenticated"
     * ),
     * @OA\Response(
     *         response="500",
     *         description="Internal Error"
     *  )
     *)
     * @param string $type
     * @param string $uuid
     * @param DMSService $dmsService
     * @param GeneralUtility $generalUtility
     * @return View
     */
    public function delete(string $type, string $uuid, DMSService $dmsService, GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            $currentUserRole = $this->currentRole;
            $role = $dmsService->convertSnakeCaseString($currentUserRole);
            $method = $role . 'Folder';
            if (!method_exists($dmsService, $method)) {
                throw new BadMethodCallException("methodNotFound");
            }
            $entity = $dmsService->fetch($type, $uuid);
            if (!$entity instanceof ReturnableDocumentInterface) {
                throw new EntityNotFoundException('entityNotFound');
            }
            $result = [];
            $class = get_class($entity);
            $parent = null;
            if ($entity instanceof Document) {
                $parent = $entity->getFolder()->getPublicId();
            } else if ($entity instanceof Folder) {
                $parent = $entity->getParent()->getPublicId();
            }
            $em->getRepository($class)->delete($entity);
            $em->refresh($entity);
            if (!$entity instanceof TemporaryUpload) {
                $result = $dmsService->$method($this->getUser()->getIdentifier(), $parent);
            }
            $data = $generalUtility->handleSuccessResponse('documentDeleteSuccess', $result);
            $em->commit();
        } catch (InvalidArgumentException | FileNotFoundException | EntityNotFoundException | \Exception $e) {
            $em->rollback();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }
        return $this->response($data);
    }

    /**
     * API end point to upload camera documents to folders.
     *
     * # Request
     * ### Example request to upload property document expects fileData, type as property, and fileName, isEncode
     * ### Example request to apartment document expects fileData, type as apartment, isEncode
     * ### Example request to contract document expects fileData, type as contract, isEncode
     * ### Example request to floor plan expects fileData, type as floorPlan, isEncode
     * ### Example request to upload property/apartment coverImage expects fileData, type as coverImage, isEncode
     * ### Example request to ticket image expects fileData, folder(ticket id), fileName, permission, type as ticket and subType (photos, floorPlan, barCode, defect, confirm)
     *
     *
     * In request body, system expects documents as a form type request and not JSON.
     *   {
     *       "type": "floorPlan",
     *       "fileData": ""
     *       "fileName": "test.jpeg",
     *       "apartment": "{{object}}",
     *       "permission": "public",
     *       "folder": "1ed08127-395a-6b2e-b74e-0242ac120004",
     *        "isEncode": true
     *   }
     * ### Route /api/2.0/document/upload
     *
     * # Response
     * ## Success response ##
     *      {
     *          "data": [
     *              {
     *                  "identifier": 14,
     *                  "public_id": "1ecd1a8a-5a5a-6740-b87e-0242ac120003",
     *                  "created_at": "2022-05-12T04:04:40+00:00",
     *                  "deleted": false,
     *                  "original_file_name": "applsci-11-10633-v2.pdf",
     *                  "file_size": 874240.0,
     *                  "temporary_upload_path": "/var/www/app/files/documents_temp/propertyDocument/applsci1110633v2-1652328280627c8758167e6.pdf",
     *                  "mime_type": "application/pdf",
     *                  "doc_type": "propertyDocument",
     *                  "file_path": "http://localhost:8001/files/documents_temp/propertyDocument/applsci1110633v2-1652328280627c8758167e6.pdf",
     *                  "local_file_name": "applsci1110633v2-1652328280627c8758167e6.pdf"
     *              }
     *          ],
     *          "error": false,
     *          "message": "uploadSuccess"
     *      }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Upload failed"
     *       }
     * @Route("/camera-upload", name="balu_camera_document_upload", methods={"POST"})
     * @Operation(
     *      tags={"Document"},
     *      summary="API end point to upload camera documents.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *              @OA\Property(property="fileData", type="string", default="", example=""),
     *              @OA\Property(property="fileName", type="string", default="myfile", example="myfile"),
     *              @OA\Property(property="type", type="string", default="property", example="property"),
     *              @OA\Property(property="subType", type="string", default="default", example="barCode"),
     *              @OA\Property(property="permission", type="boolean", default="public", example="public"),
     *              @OA\Property(property="property", type="string", default="1ecd1a8a-5a5a-6740-b87e-0242ac120003", example="1ecd1a8a-5a5a-6740-b87e-0242ac120003"),
     *              @OA\Property(property="apartment", type="string", default="1ecd1a8a-5a5a-6740-b87e-0242ac120003", example="1ecd1a8a-5a5a-6740-b87e-0242ac120003"),
     *              @OA\Property(property="contract", type="string", default="1ecd1a8a-5a5a-6740-b87e-0242ac120003", example="1ecd1a8a-5a5a-6740-b87e-0242ac120003"),
     *              @OA\Property(property="folder", type="string", default="1ecd1a8a-5a5a-6740-b87e-0242ac120003", example="1ecd1a8a-5a5a-6740-b87e-0242ac120003")
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
     * @param FileUploadHelper $fileUploadHelper
     * @param GeneralUtility $generalUtility
     * @param ValidationUtility $validationUtility
     * @param string|null $publicId
     * @return View
     * @throws \Exception
     */
    public function cameraUpload(Request $request, FileUploadHelper $fileUploadHelper, GeneralUtility $generalUtility,
                                 ValidationUtility $validationUtility, ?string $publicId = null, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $aViolations = $validationUtility->validateData('CameraDocumentUpload', $request->request->all());
        if ($aViolations) {
            $data = $generalUtility->handleFailedResponse('mandatoryFieldsMissing', 400,
                $generalUtility->formatErrors($aViolations));
            return $this->response($data);
        }
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            $aData = $request->request->all();
            if (!is_null($publicId)) {
                $aData['publicId'] = $publicId;
            }
            $aData['isEncode'] = $aData['isEncode'] ?? false;
            $aData['isEncode'] = filter_var($aData['isEncode'], FILTER_VALIDATE_BOOLEAN);
            $fileNames = $fileUploadHelper->uploadCameraDocument($this->getUser(), $request->getSchemeAndHttpHost(), $aData);
            $em->flush();
            $em->commit();
            $data = $generalUtility->handleSuccessResponse('uploadSuccess', $fileNames);
        } catch (EntityNotFoundException | FileException | \Exception $e) {
            $em->rollback();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse('uploadFail');
        }
        return $this->response($data);
    }

    /**
     * API end point to view document.
     *
     * ### Route /api/2.0/document/view
     * # Request
     * ## System expects file path in the query
     * # Response
     * ## Success response ##
     *       BinaryFileResponse
     * ## Failed response ##
     * @Route("/view", name="balu_view_document", methods={"GET"})
     * @Operation(
     *      tags={"Document"},
     *      summary="API end point to view document.",
     *      @Security(name="Bearer"),
     *      @OA\Parameter(
     *       name="path",
     *       in="query",
     *       description="File path ",
     *       required=true,
     *       @OA\Schema(
     *         type="string",
     *       ),
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Returned when request is not valid"
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Internal Error"
     *     )
     *)
     * @param Request $request
     * @param FileUploaderUtility $fileUploaderUtility
     * @param LoggerInterface $requestLogger
     * @return BinaryFileResponse
     */
    public function viewDocument(Request $request, FileUploaderUtility $fileUploaderUtility, LoggerInterface $requestLogger): BinaryFileResponse
    {
        $curDate = new \DateTime('now');
        try {
            $file = $this->parameterBag->get('damage_path') . $request->get('path');
            $pathParts = pathinfo($file);
            $compressedFile = $pathParts['dirname'] . '/' . $pathParts['filename'] . '.zip';
            $fileUploaderUtility->decompressFile($compressedFile);
            if (!file_exists($file)) {
                throw new NotFoundHttpException();
            }
            $response = new BinaryFileResponse($file);
            $response->deleteFileAfterSend(file_exists($compressedFile));
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE);
            return $response;
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            throw new NotFoundHttpException();
        }
    }

    /**
     * API end point to get encoded file resource.
     * ### api/2.0/document/encoded-resource/1ed8cde8-40e7-6354-bbaa-5254a2026859
     * In request body, system expects object details as JSON.
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
     * @Route("/encoded-resource/{resource}/{type}", name="balu_get_encoded_resource", methods={"GET"})
     * @Operation(
     *      tags={"Document"},
     *      summary="API end point to get encoded resource.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
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
     * @param string $resource
     * @param Request $request
     * @param FileUploaderUtility $fileUploaderUtility
     * @param DMSService $dmsService
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param string|null $type
     * @return View
     */
    public function getEncodedResource(string $resource, Request $request, DMSService $dmsService, FileUploaderUtility $fileUploaderUtility,
                                       GeneralUtility $generalUtility, LoggerInterface $requestLogger, string $type = null): View
    {
        try {
            $em = $this->doctrine->getManager();
            $entity = strcmp('temp', $type) === 0 ? 'TemporaryUpload' : 'Document';
            $document = $em->getRepository('App\Entity\\' . $entity)->findOneBy(['publicId' => $resource]);
            if ($dmsService->checkInstance($document, ['TemporaryUpload', 'Document'])) {
                throw new InvalidArgumentException('invalidResource');
            }
            $result = $dmsService->getUploadInfo($document, $request->getSchemeAndHttpHost(), true);
//            $method = $document instanceof Document ? 'getStoredPath' : 'getTemporaryUploadPath';
            $data = $generalUtility->handleSuccessResponse('resourceFetchedSuccessfully', $result);
        } catch (InvalidArgumentException | ResourceNotFoundException | \Exception $e) {
            $curDate = new \DateTime('now');
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }
}
