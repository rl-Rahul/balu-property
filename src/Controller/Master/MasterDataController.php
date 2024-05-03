<?php


namespace App\Controller\Master;

use App\Controller\BaseController;
use App\Service\MasterService;
use App\Utils\GeneralUtility;
use FOS\RestBundle\View\View;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

/**
 * Class MasterDataController
 *
 * Controller to get master data.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 * @Route("/master")
 */
final class MasterDataController extends BaseController
{
    /**
     * API end point to list master data types.
     *
     * ## Response
     *      {
     *          "data": [
     *              {
     *                  "type": "floor",
     *                   "name": "Floor"
     *               },
     *              {
     *                  "type": "landIndex",
     *                  "name": "Land Index"
     *              },
     *              {
     *                  "type": "objectType",
     *                  "name": "Object Type"
     *              },
     *              {
     *                  "type": "referenceIndex",
     *                  "name": "Reference Index"
     *              }
     *          ],
     *          "error": false,
     *          "message": "Data fetched"
     *      }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": ""
     *       }
     * @Operation(
     *      tags={"Master"},
     *      summary="API end point to list master data types.",
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
     * @Route("/list", name="balu_master_data_list", methods={"GET"})
     *
     * @param LoggerInterface $requestLogger
     * @param GeneralUtility $generalUtility
     * @param MasterService $masterService
     * @return View
     */
    public function getMasterDataTypes(LoggerInterface $requestLogger, GeneralUtility $generalUtility, MasterService $masterService): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $data = $generalUtility->handleSuccessResponse('noDatatoProcess', null, false, 404);
        try {
            $data = $generalUtility->handleSuccessResponse('listFetchSuccess', $masterService->getMasterDataTypes($this->locale));
        } catch (\Exception $e) {
            $data = $generalUtility->handleFailedResponse($e->getMessage());
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to list master data based on types.
     *
     * ## Response
     *      {
     *          "data": [
     *              {
     *                  "publicId": "1ed4ac17-0029-625a-a4b5-00155d01d845",
     *                  "nameDe": "Wohnung"
     *               },
     *              {
     *                  "publicId": "1ed4ac17-002b-60a0-b151-00155d01d845",
     *                  "nameDe": "Möblierte Wohnung"
     *              },
     *              {
     *                  "publicId": "1ed4ac17-002b-64ce-a80c-00155d01d845",
     *                  "nameDe": "Freistehendes Haus"
     *              },
     *              {
     *                  "publicId": "1ed4ac17-002b-6820-ae46-00155d01d845",
     *                  "nameDe": "Büro-/Praxisräume"
     *              },
     *              {
     *                  "publicId": "1ed4ac17-002b-6b40-9714-00155d01d845",
     *                  "nameDe": "Lagerhäuser"
     *              }
     *          ],
     *          "error": false,
     *          "message": "Data fetched_de"
     *    }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": ""
     *       }
     * @Operation(
     *      tags={"Master"},
     *      summary="API end point to list master data based on types.",
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
     * @Route("/list/{type}", name="balu_master_data_based_on_type", methods={"GET"})
     *
     * @param string $type
     * @param LoggerInterface $requestLogger
     * @param GeneralUtility $generalUtility
     * @param MasterService $masterService
     * @param Request $request
     * @return View
     */
    public function getMasterDataBasedOnTypes(string $type, LoggerInterface $requestLogger, GeneralUtility $generalUtility, MasterService $masterService, Request $request): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $param['searchKey'] = $request->get('searchKey');
        try {
            $data = $generalUtility->handleSuccessResponse('listFetchSuccess', $masterService->getMasterDataBasedOnTypes($type, $this->locale, $param));
        } catch (\Exception $e) {
            $data = $generalUtility->handleFailedResponse($e->getMessage());
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $e->getMessage());
        }

        return $this->response($data);
    }
}