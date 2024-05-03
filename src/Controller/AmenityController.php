<?php

/**
 * This file is part of the Balu Property package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Service\AmenityService;
use App\Utils\GeneralUtility;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\View\View;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Security;
use Psr\Log\LoggerInterface;

/**
 * AmenityController
 *
 * Controller to manage Amenity related actions.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 * @Route("/amenity")
 */
final class AmenityController extends BaseController
{
    /**
     * API end point to get list of amenities.
     *
     * # Response
     * ## Success response ##
     *       {
     *           "data": {
     *               {
     *                   "publicId": {
     *                       "uid": "1ec9ecb1-1f4f-6cb8-8723-0242ac120004"
     *                   },
     *                   "name": "Balcony / Terrace / Loggia",
     *                   "key": "bal",
     *                   "isInput": true
     *               }
     *           },
     *           "error": false,
     *           "message": "Data fetched"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "fail"
     *       }
     * @Route("/list", name="balu_get_amenities", methods={"GET"})
     * @Operation(
     *      tags={"Amenity"},
     *      summary="API end point to get list of active amenities.",
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
     * @param Request $request
     * @param AmenityService $amenityService
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function getAmenities(Request $request, AmenityService $amenityService, GeneralUtility $generalUtility, LoggerInterface $requestLogger): view
    {
        $curDate = new \DateTime('now');
        $data = $generalUtility->handleSuccessResponse('fail');
        try {
            $amenities = $amenityService->getAmenities($request, $this->locale);
            $data = $generalUtility->handleSuccessResponse('listFetchSuccess', $amenities);
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
        }

        return $this->response($data);
    }
}