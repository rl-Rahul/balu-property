<?php

namespace App\Controller;

use App\Entity\Category;
use App\Utils\GeneralUtility;
use FOS\RestBundle\View\View;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use App\Entity\FavouriteCompany;
use App\Entity\UserIdentity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use App\Service\UserService;
use App\Utils\ValidationUtility;
use Psr\Log\LoggerInterface;

/**
 * FavouriteController
 *
 * Controller to manage favourite/unfavourite actions
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 * @Route("/favourite")
 */
final class FavouriteController extends BaseController
{
    /**
     * API end point to favourite a company
     *
     * roles: company/property_admin/individual
     * # Request
     *      {
     *          "favouriteUser": "1eca4592-ff84-6632-ba77-0242ac120004"
     *      }
     *
     * ## Success response ##
     *
     *      {
     *      "data": "No data provided",
     *      "error": false,
     *      "message": "success"
     *      }
     * @Operation(
     *      tags={"Favourite"},
     *      summary="API end point to favourite a company by a user",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="favouriteUser", type="string", default="", example="")
     *          )
     *       )
     *     ),
     *  @OA\Response(
     *     response=200,
     *     description="Returns Company category lists",
     *  ),
     *  @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     *  ),
     *  @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     *  ),
     *  @OA\Tag(name="Favourite")
     * )
     * @param string $role
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param UserService $userService
     * @param ValidationUtility $validationUtility
     * @return View
     * @Route("/{role}", name="balu_company_favourite", methods={"POST"})
     */
    public function favourite(string $role, Request $request, GeneralUtility $generalUtility, UserService $userService, ValidationUtility $validationUtility, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        try {
            //validate the user and role. Gets back directory entity
            $favouriteUser = $validationUtility->validateFavouriteUserAndRole($request->request->get('favouriteUser'), $role);
            //process favourite according to the user role type
            $successMessage = $userService->processFavourite($favouriteUser, $this->getUser(), $role);
            $data = $generalUtility->handleSuccessResponse($successMessage, null);
        } catch (\InvalidArgumentException | \Exception | UniqueConstraintViolationException $e) {
            $data = $generalUtility->handleFailedResponse($e->getMessage());
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
        }
        return $this->response($data);
    }
}
