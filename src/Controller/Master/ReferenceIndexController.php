<?php


namespace App\Controller\Master;


use App\Controller\BaseController;
use App\Entity\ReferenceIndex;
use App\Form\ReferenceIndexType;
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
 * ReferenceIndexController
 *
 * Controller to get reference index items.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 * @Route("/reference-index")
 */
final class ReferenceIndexController extends BaseController
{
    /**
     * API end point to create new master data for reference index.
     *
     * # Request
     * In request body, system expects object details as JSON.
     * ## Example request to create new object
     *       {
     *           "name": "test",
     *           "sortOrder": 11,
     *           "active": true,
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
     *      summary="API end point to create new master data for reference index.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="name", type="string", default="test", example="test"),
     *               @OA\Property(property="sortOrder", type="integer", default="11", example="11"),
     *               @OA\Property(property="active", type="boolean", default="true", example="true"),
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
     * @Route("/add", name="balu_ref_index_add", methods={"POST"})
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
        $refIndex = new ReferenceIndex();
        $form = $this->createNamedForm(ReferenceIndexType::class, $refIndex);
        $form->submit($request->request->all());
        $data = $generalUtility->handleFailedResponse('formError', 400, $this->getErrorsFromForm($form));
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->beginTransaction();
            try {
                $data = $generalUtility->handleSuccessResponse('masterDataAddSuccess', $masterService->saveReferenceIndexData($request->request->all()));
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
     * API end point to edit master data for reference index.
     *
     * # Request
     * In request body, system expects object details as JSON.
     * ## Example request to create new object
     *       {
     *           "name": "A5"
     *           "sortOrder": 11,
     *           "active": true
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
     *      summary="API end point to edit master data for reference index.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="name", type="string", default="name", example="name"),
     *               @OA\Property(property="sortOrder", type="integer", default="1", example="1"),
     *               @OA\Property(property="active", type="boolean", default="true", example="true"),
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
     * @Route("/edit/{refIndexId}", name="balu_ref_index_edit", methods={"PUT"})
     *
     * @param string $refIndexId
     * @param Request $request
     * @param LoggerInterface $requestLogger
     * @param GeneralUtility $generalUtility
     * @param MasterService $masterService
     * @return View
     */
    public function edit(string $refIndexId, Request $request, LoggerInterface $requestLogger, GeneralUtility $generalUtility, MasterService $masterService): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $refIndex = $em->getRepository(ReferenceIndex::class)->findOneBy(['publicId' => $refIndexId, 'deleted' => false]);
        $form = $this->createNamedForm(ReferenceIndexType::class, $refIndex);
        $form->submit($request->request->all());
        $data = $generalUtility->handleFailedResponse('formError', 400, $this->getErrorsFromForm($form));
        if ($form->isSubmitted() && $form->isValid()) {
            $em->beginTransaction();
            try {
                $data = $generalUtility->handleSuccessResponse('masterDataEditSuccess', $masterService->updateReferenceIndex($refIndex, $request->request->all()));
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
     * API end point to view master data for reference index.
     *
     * # Request
     * In request body, system expects object details as JSON.
     * ## Example request to create new object
     *       {
     *           "refIndexId": "A52323-d343-434r-34324rr"
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
     *      summary="API end point to view master data for reference index.",
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
     * @Route("/view/{refIndexId}", name="balu_ref_index_view", methods={"GET"})
     *
     * @param string $refIndexId
     * @param LoggerInterface $requestLogger
     * @param GeneralUtility $generalUtility
     * @param MasterService $masterService
     * @return View
     */
    public function viewReferenceIndexDetails(string $refIndexId, LoggerInterface $requestLogger, GeneralUtility $generalUtility, MasterService $masterService): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $data = $generalUtility->handleSuccessResponse('noDatatoProcess', null, false, 404);
        try {
            $refIndex = $em->getRepository(ReferenceIndex::class)->findOneBy(['publicId' => $refIndexId, 'deleted' => false]);
            if ($refIndex instanceof ReferenceIndex) {
                $data = $generalUtility->handleSuccessResponse('listFetchSuccess', $masterService->getReferenceIndexData($refIndex));
            }
        } catch (\Exception $e) {
            $data = $generalUtility->handleFailedResponse($e->getMessage());
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to delete master data for reference index.
     *
     * # Request
     * In request body, system expects object details as JSON.
     * ## Example request to create new object
     *       {
     *           "refIndexId": "A52323-d343-434r-34324rr"
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
     *      summary="API end point to delete master data for reference index.",
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
     * @Route("/delete/{refIndexId}", name="balu_ref_index_delete", methods={"DELETE"})
     *
     * @param string $refIndexId
     * @param Request $request
     * @param LoggerInterface $requestLogger
     * @param GeneralUtility $generalUtility
     * @return View
     */
    public function delete(string $refIndexId, Request $request, LoggerInterface $requestLogger, GeneralUtility $generalUtility): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        try {
            $em->beginTransaction();
            $refIndex = $em->getRepository(ReferenceIndex::class)->findOneBy(['publicId' => $refIndexId]);
            $refIndex->setDeleted(true);
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