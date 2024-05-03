<?php


namespace App\Controller\Master;


use App\Controller\BaseController;
use App\Entity\Floor;
use App\Form\FloorType;
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
 * FloorController
 *
 * Controller to get floor items.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 * @Route("/floor")
 */
final class FloorController extends BaseController
{
    /**
     * API end point to create new master data for floor.
     *
     * # Request
     * In request body, system expects object details as JSON.
     * ## Example request to create new object
     *       {
     *           "floorNumber": "A5"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": ""
     *       }
     * @Operation(
     *      tags={"Master"},
     *      summary="API end point to create new master data for floor.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="floorNumber", type="string", default="12A", example="12A"),
     *               @OA\Property(property="sortOrder", type="int", default="1", example="1"),
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
     * @Route("/add", name="balu_floor_add", methods={"POST"})
     *
     * @param Request $request
     * @param LoggerInterface $requestLogger
     * @param GeneralUtility $generalUtility
     * @param MasterService $masterService
     * @return View
     */
    public function add(Request $request, LoggerInterface $requestLogger, GeneralUtility $generalUtility, MasterService $masterService): View
    {
        $curDate = new \DateTime('now');
        $floor = new Floor();
        $form = $this->createNamedForm(FloorType::class, $floor);
        $form->submit($request->request->all());
        $data = $generalUtility->handleFailedResponse('formError', 400, $this->getErrorsFromForm($form));
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->beginTransaction();
            try {
                $data = $generalUtility->handleSuccessResponse('masterDataAddSuccess', $masterService->saveFloorData($request->request->all()));
                $em->flush();
                $em->commit();
            } catch (\Exception $e) {
                $em->rollBack();
                $data = $generalUtility->handleFailedResponse($e->getMessage());
                $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            }
        }

        return $this->response($data);
    }

    /**
     * API end point to edit master data for floor.
     *
     * # Request
     * In request body, system expects object details as JSON.
     * ## Example request to create new object
     *       {
     *           "floorNumber": "A5"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": ""
     *       }
     * @Operation(
     *      tags={"Master"},
     *      summary="API end point to edit master data for floor.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="floorNumber", type="string", default="12A", example="12A"),
     *               @OA\Property(property="sortOrder", type="int", default="1", example="1"),
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
     * @Route("/edit/{floorId}", name="balu_floor_edit", methods={"PUT"})
     *
     * @param string $floorId
     * @param Request $request
     * @param LoggerInterface $requestLogger
     * @param GeneralUtility $generalUtility
     * @param MasterService $masterService
     * @return View
     */
    public function edit(string $floorId, Request $request, LoggerInterface $requestLogger, GeneralUtility $generalUtility, MasterService $masterService): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $floor = $em->getRepository(Floor::class)->findOneBy(['publicId' => $floorId, 'deleted' => false]);
        $form = $this->createNamedForm(FloorType::class, $floor);
        $form->submit($request->request->all());
        $data = $generalUtility->handleFailedResponse('formError', 400, $this->getErrorsFromForm($form));
        if ($form->isSubmitted() && $form->isValid()) {
            $em->beginTransaction();
            try {
                $data = $generalUtility->handleSuccessResponse('masterDataEditSuccess', $masterService->updateFloorData($floor, $request->request->all()));
                $em->flush();
                $em->commit();
            } catch (\Exception $e) {
                $em->rollBack();
                $data = $generalUtility->handleFailedResponse($e->getMessage());
                $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            }
        }

        return $this->response($data);
    }

    /**
     * API end point to create view master data for floor.
     *
     * # Request
     * In request body, system expects object details as JSON.
     * ## Example request to create new object
     *       {
     *           "floorId": "A52323-d343-434r-34324rr"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": ""
     *       }
     * @Operation(
     *      tags={"Master"},
     *      summary="API end point to view master data for floor.",
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
     * @Route("/view/{floorId}", name="balu_floor_view", methods={"GET"})
     *
     * @param string $floorId
     * @param LoggerInterface $requestLogger
     * @param GeneralUtility $generalUtility
     * @param MasterService $masterService
     * @return View
     */
    public function viewFloorDetails(string $floorId, LoggerInterface $requestLogger, GeneralUtility $generalUtility, MasterService $masterService): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $data = $generalUtility->handleSuccessResponse('noDatatoProcess', null, false, 404);
        try {
            $floor = $em->getRepository(Floor::class)->findOneBy(['publicId' => $floorId, 'deleted' => false]);
            if ($floor instanceof Floor) {
                $data = $generalUtility->handleSuccessResponse('listFetchSuccess', $masterService->getFloorData($floor));
            }
        } catch (\Exception $e) {
            $data = $generalUtility->handleFailedResponse($e->getMessage());
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to delete master data for floor.
     *
     * # Request
     * In request body, system expects object details as JSON.
     * ## Example request to create new object
     *       {
     *           "floorId": "A52323-d343-434r-34324rr"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": ""
     *       }
     * @Operation(
     *      tags={"Master"},
     *      summary="API end point to delete master data for floor.",
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
     * @Route("/delete/{floorId}", name="balu_floor_delete", methods={"DELETE"})
     *
     * @param string $floorId
     * @param Request $request
     * @param LoggerInterface $requestLogger
     * @param GeneralUtility $generalUtility
     * @return View
     */
    public function delete(string $floorId, Request $request, LoggerInterface $requestLogger, GeneralUtility $generalUtility): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        try {
            $em->beginTransaction();
            $floor = $em->getRepository(Floor::class)->findOneBy(['publicId' => $floorId]);
            $floor->setDeleted(true);
            $em->flush();
            $em->commit();
            $data = $generalUtility->handleSuccessResponse('masterDataDeleteSuccess');
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }
}