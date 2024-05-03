<?php

/**
 * This file is part of the Balu Property package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\UserIdentity;
use App\Form\PropertyAdminByOwnerRegistrationType;
use App\Service\RegistrationService;
use App\Service\UserService;
use App\Utils\ContainerUtility;
use App\Utils\ValidationUtility;
use Nelmio\ApiDocBundle\Annotation\Operation;
use App\Utils\GeneralUtility;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;
use App\Service\CompanyService;
use App\Entity\Feedback;
use App\Form\FeedbackType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

/**
 * GeneralController
 *
 * Controller
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 * @Route("/general")
 */
final class GeneralController extends BaseController
{
    /**
     * API end point to send feedback to admin.
     *
     * # Request
     * In request body, system expects following data.
     * ## Example request to update user settings
     *      {
     *          "subject": "Subject line",
     *          "message": "Message"
     *      }
     * # Response
     * ## Success response ##
     *      {
     *          "error": false,
     *          "data": {
     *
     *           },
     *          "message": "Feedback send successfully"
     *      }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *         "data": {
     *            "subject": "This value should not be blank.",
     *            "Message": "This value should not be blank.",
     *           },
     *         "error": true,
     *         "message": "Mandatory fields are missing"
     *      },
     *      {
     *        "data": "No data provided",
     *        "error": true,
     *        "message": "User already exists"
     *      },
     *      {
     *        "data": "No data provided",
     *        "error": true,
     *        "message": "Password Mismatch"
     *      }
     * @Route("/send", name="balu_send_feedback", methods={"POST"})
     * @Operation(
     *      tags={"General"},
     *      summary="API end point to send feedback to admin.",
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               @OA\Property(property="subject", type="string", default="", example="x"),
     *               @OA\Property(property="message", type="string", default="", example=""),
     *           )
     *       )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Returned on successful registration"
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
     * @param GeneralUtility $generalUtility
     * @param ValidationUtility $validationUtility
     * @param ContainerUtility $containerUtility
     * @return View
     * @throws \Exception
     */
    public function send(Request $request, GeneralUtility $generalUtility, ValidationUtility $validationUtility, ContainerUtility $containerUtility, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $feedBack = new Feedback();
        $form = $this->createNamedForm(FeedbackType::class, $feedBack);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->beginTransaction();
            try {
                $feedBack->setSendBy($this->getUser());
                $em->persist($feedBack);
                $em->flush();
                $em->commit();
                $data = $generalUtility->handleSuccessResponse('feedBackSuccessfull', []);
            } catch (InvalidPasswordException | \Exception $e) {
                $em->rollback();
                $data = $generalUtility->handleFailedResponse($e->getMessage());
            }
        } else {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse('formError', 400, $this->getErrorsFromForm($form));
        }
        return $this->response($data);
    }

    /**
     * API end point to get more details of the current user
     *
     * Get required data when tapping the more button
     *
     * ## Success response ##
     *
     *      {
     *          "error": false,
     *          "data": {
     *              "profileUrl": "https://demo.mypits.org:15091/#/balu/more/profile/:token/:language"
     *          }
     *      }
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns more overview of a user",
     * ),
     * @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     * ),
     * @OA\Response(
     *     response="401",
     *     description="User not authenticated"
     * ),
     * @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     * ),
     * @OA\Response(
     *     response="422",
     *     description="User not found"
     * )
     * @Operation(
     *     summary="API end point to get more overview of a user.",
     *     @Security(name="Bearer")
     * )
     * @OA\Tag(name="General")
     * @param GeneralUtility $generalUtility
     * @param ContainerUtility $containerUtility
     * @return View
     * @throws \Exception
     * @Route("/more", name="balu_more", methods={"GET"})
     */
    public function more(GeneralUtility $generalUtility, ContainerUtility $containerUtility): View
    {
        $details = $containerUtility->moreUrlBuilder($this->getUser()->getUser(), $this->locale);
        return $this->response($generalUtility->handleSuccessResponse('moreDetailsFetched', $details));
    }
}