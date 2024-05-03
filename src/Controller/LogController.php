<?php

/**
 * This file is part of the Balu Property package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\ObjectContracts;
use App\Utils\GeneralUtility;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\View\View;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use App\Service\TenantService;
use App\Entity\Apartment;
use App\Service\ObjectService;
use Psr\Log\LoggerInterface;

/**
 * LogController
 *
 * Controller to manage log related actions.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 * @Route("/log")
 */
final class LogController extends BaseController
{

    /**
     * API end point to get log of contract.
     * # Response
     * ## Success response ##
     *       {
     *          "data": [],
     *          "error": false,
     *          "message": "Details fetched successfully"
     *        }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": [
     *              {
     *                   "publicId": "1ecfa9a6-c5bf-685c-bf1d-0242ac120004",
     *                   "additionalComment": "sssmyssssssssssss comment",
     *                   "ownerVote": true,
     *                   "startDate": "2022-11-25T00:00:00+00:00",
     *                   "endDate": "2022-12-26T00:00:00+00:00",
     *                   "noticePeriod": {
     *                       "id": "1ecc51de-22c3-6f22-a4bc-0242ac120004",
     *                       "name": "1 month"
     *                   },
     *                   "rentalType": "1ecc51de-22cb-69d4-9abc-0242ac120004",
     *                   "user": [{}
     *               },
     *          ],
     *          "error": true,
     *          "message": "Invalid contract"
     *       }
     * @Route("/contract/{contract}", name="balu_log_contract", methods={"GET"})
     * @Operation(
     *      tags={"Log"},
     *      summary="API end point to get logs of contract.",
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
     * @param string $contract
     * @param TenantService $tenantService
     * @param GeneralUtility $generalUtility
     * @return View
     */
    public function getContractLog(string $contract, TenantService $tenantService, GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request): view
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        try {
            $oContract = $em->getRepository(ObjectContracts::class)->findOneBy(['publicId' => $contract, 'deleted' => 0]);
            if (!$oContract instanceof ObjectContracts) {
                throw new ResourceNotFoundException('invalidContract');
            }
            $result = $tenantService->getContractLog($oContract, $this->locale);
            $data = $generalUtility->handleSuccessResponse('fetchSuccess', $result);
        } catch (ResourceNotFoundException | \Exception  $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to get log of object.
     * # Response
     * ## Success response ##
     *       {
     *          "data": [],
     *          "error": false,
     *          "message": "Details fetched successfully"
     *        }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *      "data": [
     *           {
     *               "publicId": "1ed018df-1c66-6384-b11c-0242ac120004",
     *               "area": 123.0,
     *               "roomCount": "2",
     *               "name": "vidyas object",
     *               "objectType": "1ecd2886-5eae-6d8c-b623-0242ac120004",
     *               "sortOrder": 1,
     *               "officialNumber": 1,
     *               "floor": "1ecc51de-22b8-6294-a0ac-0242ac120004",
     *               "createdBy": "1ecc51de-292a-63fc-8c7b-0242ac120004",
     *               "totalObjectValue": 1.0,
     *               "additionalCostBuilding": 100.0,
     *               "additionalCostEnvironment": 100.0,
     *               "additionalCostHeating": 100.0,
     *               "additionalCostElevator": 100.0,
     *               "additionalCostParking": 100.0,
     *               "additionalCostRenewal": 100.0,
     *               "additionalCostMaintenance": 100.0,
     *               "additionalCostAdministration": 100.0,
     *               "additionalCost": "4",
     *               "contractType": "1ecc51de-22b3-6136-a78b-0242ac120004",
     *               "additionalCostCurrency": "1ecd4e52-b69a-63c4-aa49-0242ac120004",
     *               "modeOfPayment": "1ecd4e57-bf0f-6a68-a2e1-0242ac120004"
     *           }
     *       ],
     *       "error": false,
     *       "message": "fetchSuccess"
     *   }
     * @Route("/object/{object}", name="balu_log_object", methods={"GET"})
     * @Operation(
     *      tags={"Log"},
     *      summary="API end point to get logs of object.",
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
     * @param string $object
     * @param GeneralUtility $generalUtility
     * @return View
     */
    public function getObjectLog(string $object, ObjectService $objectService, GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request): view
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        try {
            $oObject = $em->getRepository(Apartment::class)->findOneBy(['publicId' => $object, 'deleted' => 0]);
            if (!$oObject instanceof Apartment) {
                throw new ResourceNotFoundException('invalidObject');
            }
            $result = $objectService->getObjectLog($oObject, $this->locale);
            $data = $generalUtility->handleSuccessResponse('fetchSuccess', $result);
        } catch (ResourceNotFoundException | \Exception  $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to get rent history of object.
     * # Response
     * ## Success response ##
     *       {
     *          "data": [],
     *          "error": false,
     *          "message": "Details fetched successfully"
     *        }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *       "data": {
     *           "modeOfPayment": "online",
     *           "referenceIndex": "",
     *           "additionalCost": 4.0,
     *           "netRentRate": ""
     *       },
     *       "error": false,
     *       "message": "fetchSuccess"
     *   }
     * @Route("/object/rent-history/{object}", name="balu_rent_history_object", methods={"GET"})
     * @Operation(
     *      tags={"Log"},
     *      summary="API end point to get rent history of object.",
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
     * @param string $object
     * @param GeneralUtility $generalUtility
     * @return View
     */
    public function getRentHistory(string $object, ObjectService $objectService, GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request): view
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        try {
            $oObject = $em->getRepository(Apartment::class)->findOneBy(['publicId' => $object, 'deleted' => 0]);
            if (!$oObject instanceof Apartment) {
                throw new ResourceNotFoundException('invalidObject');
            }
            $result = $objectService->getRentHistory($oObject, $this->locale);
            $data = $generalUtility->handleSuccessResponse('fetchSuccess', $result);
        } catch (ResourceNotFoundException | \Exception  $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }
}
