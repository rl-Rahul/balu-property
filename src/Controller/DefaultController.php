<?php

/**
 * This file is part of the Balu Property package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Floor;
use App\Entity\ContractTypes;
use App\Helpers\FileUploadHelper;
use App\Service\DMSService;
use App\Utils\GeneralUtility;
use App\Utils\ValidationUtility;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use App\Entity\RentalTypes;
use App\Entity\NoticePeriod;
use App\Entity\ObjectTypes;
use App\Entity\LandIndex;
use App\Entity\ReferenceIndex;
use App\Entity\ModeOfPayment;
use App\Entity\Currency;
use App\Entity\MessageType;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Nelmio\ApiDocBundle\Annotation\Security;
use Psr\Log\LoggerInterface;

/**
 * DefaultController
 *
 * Controller to get default items.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 * @Route("/defaults")
 */
final class DefaultController extends BaseController
{
    /**
     * API end point to list defaults
     *
     *
     * # Request
     * No need to pass params to get the values
     *
     * ## Success response ##
     *
     *      {
     *           "data": {
     *               "objectType": [],
     *               "landIndex": [],
     *               "referenceIndex": [],
     *               "rentalTypes": [],
     *               "floor": [],
     *               "contractTypes": [],
     *               "noticePeriod": [],
     *               "modeOfPaymet": [],
     *               "currency": []
     *           },
     *           "error": false,
     *           "message": "Data fetched"
     *       }
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns default floor and contract lists",
     * ),
     * @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     * ),
     * @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     * ),
     * @OA\Tag(name="Defaults")
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @return View
     * @Route("/list", name="balu_default_list", methods={"GET"})
     */
    public function getList(GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request): View
    {
        $curDate = new \DateTime('now');
        try {
            $list['objectType'] = $this->doctrine->getRepository(ObjectTypes::class)->findAllBy($this->locale);
            $list['floor'] = $this->doctrine->getRepository(Floor::class)->findAllBy($this->locale);
            $list['landIndex'] = $this->doctrine->getRepository(LandIndex::class)->findAllBy($this->locale);
            $list['referenceIndex'] = $this->doctrine->getRepository(ReferenceIndex::class)->findAllBy($this->locale);
            $list['contractType'] = $this->doctrine->getRepository(ContractTypes::class)->findAllBy($this->locale);
            $list['rentalTypes'] = $this->doctrine->getRepository(RentalTypes::class)->findAllBy($this->locale);
            $list['noticePeriod'] = $this->doctrine->getRepository(NoticePeriod::class)->findAllBy($this->locale);
            $list['modeOfPayment'] = $this->doctrine->getRepository(ModeOfPayment::class)->findAllBy($this->locale);
            $list['currency'] = $this->doctrine->getRepository(Currency::class)->findAllBy($this->locale);
            $list['messageTypes'] = $this->doctrine->getRepository(MessageType::class)->findAllBy($this->locale);
            $data = $generalUtility->handleSuccessResponse('listFetchSuccess', $list);
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleSuccessResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point for default file upload.
     *
     * # Request
     * In request body, system expects documents as a form type request and not JSON.
     * ### Example request to upload property document expects files[], type as property, folder,isEncode
     * ### Example request to apartment document expects files[], type as property,isEncode
     * ### Example request to contract document expects files[], type as contract,isEncode
     * ### Example request to floor plan expects files[], type as floorPlan
     * ### Example request to upload property/apartment cover image expects files[], type as coverImage, folder,isEncode
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
     * @Route("/file/upload", name="balu_file_upload", methods={"POST"})
     * @Operation(
     *      tags={"Defaults"},
     *      summary="API end point to upload documents.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *              @OA\Property(property="files[]", type="file", default="", example="uploads/ProfileImage/610b6a008ec44/screenshot4-610b6a008ef0a.png"),
     *              @OA\Property(property="type", type="string", default="property", example=""),
     *              @OA\Property(property="pdfToJpg", type="boolean", default="false", example="0"),
     *              @OA\Property(property="page", type="string", default="1", example="1"),
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
     * @param string|null $folder
     * @return View
     * @throws \Exception
     */
    public function upload(Request $request, FileUploadHelper $fileUploadHelper, GeneralUtility $generalUtility,
                           ValidationUtility $validationUtility, LoggerInterface $requestLogger, ?string $folder = null): View
    {
        $curDate = new \DateTime('now');
        $pdfToJpg = !empty($request->get('pdfToJpg')) && $request->get('pdfToJpg') == true;
        $page = !empty($request->get('page')) ? $request->get('page') : 1;
        $files = array_merge($request->files->all());
        $request->request->set('files[]', $files);
        $aViolations = $validationUtility->validateData('Upload', $request->request->all());
        if ($aViolations) {
            $data = $generalUtility->handleFailedResponse(array_values($aViolations)[0][0], 400,
                $generalUtility->formatErrors($aViolations));
            return $this->response($data);
        }
        $em = $this->doctrine->getManager();
        try {
            $aFiles = $request->files->all();
            $aData = $request->request->all();
            $objectType = $aData['type'];
            $isEncode = $aData['isEncode'] ?? false;
            $isEncode = filter_var($isEncode, FILTER_VALIDATE_BOOLEAN);
            $fileNames = $fileUploadHelper->tempUploadDocument($objectType, $aFiles, $request->getSchemeAndHttpHost(), $isEncode, $pdfToJpg, $page, $aData);
            $em->flush();
            $data = $generalUtility->handleSuccessResponse('uploadSuccess', $fileNames);
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse('uploadFail');
        }
        return $this->response($data);
    }

    /**
     * API end point for file upload as data url
     *
     * # Request
     * In request body, system expects JSON.
     * ### Example request to upload property document expects fileData, type as property, isEncode, and fileName
     * ### Example request to apartment document expects fileData, type as apartment, isEncode
     * ### Example request to contract document expects fileData, type as contract, isEncode
     * ### Example request to floor plan expects fileData, type as floorPlan, isEncode
     * ### Example request to upload property/apartment coverImage expects fileData, type as coverImage, isEncode
     *
     *   {
     *       "type": "apartment",
     *       "fileData":"",
     *       "fileName" : "test"
     *   }
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
     * @Route("/file/data-url-upload", name="balu_data_url_upload", methods={"POST"})
     * @Operation(
     *      tags={"Defaults"},
     *      summary="API end point to upload documents as data url",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="type", type="string", default="", example="apartment"),
     *               @OA\Property(property="fileName", type="string", default="", example=""),
     *               @OA\Property(property="fileData", type="string", default="", example=""),
     *               @OA\Property(property="isEncode", type="string")
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
     * @return View
     * @throws \Exception
     */
    public function datUrlUpload(Request $request, FileUploadHelper $fileUploadHelper, GeneralUtility $generalUtility, ValidationUtility $validationUtility, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
//        $aViolations = $validationUtility->validateData('CameraUpload', $request->request->all());
//        if ($aViolations) {
//            $data = $generalUtility->handleFailedResponse('mandatoryFieldsMissing', 400,
//                $generalUtility->formatErrors($aViolations));
//            return $this->response($data);
//        }
        $em = $this->doctrine->getManager();
        try {
            $aData = $request->request->all();
            $objectType = $aData['type'];
            $isEncode = $aData['isEncode'] ?? false;
            $isEncode = filter_var($isEncode, FILTER_VALIDATE_BOOLEAN);
            $fileNames = $fileUploadHelper->uploadCameraTempDocument($objectType, $aData, $request->getSchemeAndHttpHost(), $isEncode);
            $em->flush();
            $data = $generalUtility->handleSuccessResponse('uploadSuccess', $fileNames);
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }
        return $this->response($data);
    }
}