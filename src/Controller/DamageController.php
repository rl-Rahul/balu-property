<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\DamageRequest;
use App\Entity\UserIdentity;
use App\Form\DamageRequestType;
use App\Service\CompanyService;
use App\Service\UserService;
use App\Utils\Constants;
use App\Utils\GeneralUtility;
use Doctrine\ORM\EntityNotFoundException;
use FOS\RestBundle\View\View;
use App\Form\DamageType;
use App\Entity\Damage;
use App\Service\DamageService;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use App\Form\DamageStatusType;
use PhpParser\Node\Stmt\Else_;
use Psr\Log\LoggerInterface;

/**
 * DamageController
 *
 * Controller to manage damage/tickets actions
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 * @Route("/ticket")
 */
final class DamageController extends BaseController
{
    /**
     * API end point to create a ticket
     *  ### allocation true/false based n the ticket assignee
     * # Request
     *
     *      {
     *          "title":"title10",
     *          "description":"desc10",
     *          "isDeviceAffected":"1",
     *          "barCode":"123-34343-56536",
     *          "damageImages": [
     *               "1ecdb203-8ae0-652a-ad96-0242ac1b0004",
     *               "1ecdb203-8b0a-6e74-aecd-0242ac1b0004",
     *               "1ecdb203-8b38-609a-a363-0242ac1b0004"
     *           ],
     *          "locationImage": "1ecdb203-8b62-6a84-979e-0242ac1b0004",
     *          "barCodeImage": "1ecdb203-8b62-6a84-979e-0242ac1b0004",
     *          "preferredCompany":"1ec8e130-1d38-66f6-9036-00155d01d845",
     *          "isOfferPreferred" : "0",
     *          "apartment":"1ecd2b01-752c-6e24-abd7-00155d01d845",
     *          "sendToCompany": false,
     *          "isFloorPlanEdit": false,
     *          "allocation": true,
     *          "issueType": "1ed3ef57-9424-60b8-891e-5254a2026859"
     *       }
     *
     * ## Success response ##
     *
     *      {
     *       "data": {
     *           "publicId": {
     *               "uid": "1ecdb4c3-c5b4-6d0a-a715-0242ac1b0004"
     *           },
     *           "title": "title10",
     *           "description": "desc10",
     *           "isDeviceAffected": true,
     *           "preferredCompany": {
     *               "uid": "1ec8e130-1d38-66f6-9036-00155d01d845"
     *           },
     *           "apartment": {
     *               "uid": "1ecd2b01-752c-6e24-abd7-00155d01d845"
     *           },
     *           "barCode": "123-34343-56536",
     *           "status": "open",
     *           "damageImages": [
     *               "1ecdb4c4-03a5-6344-aead-0242ac1b0004",
     *               "1ecdb4c4-03ef-6386-974b-0242ac1b0004",
     *               "1ecdb4c4-046f-60cc-9cb1-0242ac1b0004"
     *           ],
     *           "barCodeImage": "1ecdb4c4-04db-6cea-8345-0242ac1b0004"
     *       },
     *       "error": false,
     *       "message": "damageSuccess"
     *      }
     * @Operation(
     *      tags={"Ticket"},
     *      summary="API end point to add a ticket",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="title", type="string", default="", example=""),
     *               @OA\Property(property="description", type="string", default="", example=""),
     *               @OA\Property(property="isDeviceAffected", type="string", default="false", example=""),
     *               @OA\Property(property="barCode", type="string", default="", example=""),
     *               @OA\Property(
     *                      property="damageImages",
     *                      type="array",
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003"),
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003")
     *               ),
     *               @OA\Property(property="locationImage", type="string", default="", example=""),
     *               @OA\Property(property="barCodeImage", type="string", default="", example=""),
     *               @OA\Property(property="preferredCompany", type="string", default="", example=""),
     *               @OA\Property(property="isOfferPreferred", type="string", default="", example=""),
     *               @OA\Property(property="apartment", type="string", default="", example=""),
     *               @OA\Property(property="sendToCompany", type="bool", default="", example=""),
     *               @OA\Property(property="isFloorPlanEdit", type="bool", default="", example=""),
     *               @OA\Property(property="allocation", type="bool", default="", example=""),
     *               @OA\Property(property="issueType", type="string", default="", example=""),
     *          )
     *       )
     *     ),
     *  @OA\Response(
     *     response=200,
     *     description="Returns success message after creating ticket",
     *  ),
     *  @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     *  ),
     *  @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     *  ),
     *  @OA\Tag(name="Ticket")
     * )
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param DamageService $damageService
     * @param LoggerInterface $requestLogger
     * @return View
     * @Route("/add", name="balu_ticket_create", methods={"POST"})
     */
    public function create(Request $request, GeneralUtility $generalUtility, DamageService $damageService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $damage = new Damage();
        $form = $this->createNamedForm(DamageType::class, $damage, $damageService->getFormOptions($request, $this->currentRole, $this->getUser()));
        $form->handleRequest($request);
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            if ($form->isSubmitted() && $form->isValid()) {
                $damageService->processDamage($request, $damage, $this->getUser(), $this->currentRole);
                $em->flush();
                $data = $generalUtility->handleSuccessResponse('damageSuccess',
                    $damageService->generateDamageDetails($damage, $request, $this->getUser(), true, $this->currentRole));
                $em->commit();
            } else {
                throw new InvalidArgumentException('formError');
            }
        } catch (InvalidArgumentException | UnsupportedUserException | AccessDeniedException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $em->rollBack();
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to edit a ticket
     *
     * # Request
     *      {
     *          "title":"title10",
     *          "description":"desc10",
     *          "isDeviceAffected":"1",
     *          "barCode":"123-34343-56536",
     *          "damageImages": [
     *               "1ecdb203-8ae0-652a-ad96-0242ac1b0004",
     *               "1ecdb203-8b0a-6e74-aecd-0242ac1b0004",
     *               "1ecdb203-8b38-609a-a363-0242ac1b0004"
     *           ],
     *          "locationImage": "1ecdb203-8b62-6a84-979e-0242ac1b0004",
     *          "preferredCompany":"1ec8e130-1d38-66f6-9036-00155d01d845",
     *          "isOfferPreferred" : "0",
     *          "apartment":"1ecd2b01-752c-6e24-abd7-00155d01d845"
     *      }
     *
     * ## Success response ##
     *
     *      {
     *       "data": {
     *           "publicId": {
     *               "uid": "1ecdb4c3-c5b4-6d0a-a715-0242ac1b0004"
     *           },
     *           "title": "title10",
     *           "description": "desc10",
     *           "isDeviceAffected": true,
     *           "preferredCompany": {
     *               "uid": "1ec8e130-1d38-66f6-9036-00155d01d845"
     *           },
     *           "apartment": {
     *               "uid": "1ecd2b01-752c-6e24-abd7-00155d01d845"
     *           },
     *           "barCode": "123-34343-56536",
     *           "status": "open",
     *           "damageImages": [
     *               "1ecdb4c4-03a5-6344-aead-0242ac1b0004",
     *               "1ecdb4c4-03ef-6386-974b-0242ac1b0004",
     *               "1ecdb4c4-046f-60cc-9cb1-0242ac1b0004"
     *           ],
     *           "locationImage": "1ecdb4c4-04db-6cea-8345-0242ac1b0004"
     *       },
     *       "error": false,
     *       "message": "damageSuccess"
     *      }
     * @Operation(
     *      tags={"Ticket"},
     *      summary="API end point to edit a ticket",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="title", type="string", default="", example=""),
     *               @OA\Property(property="description", type="string", default="", example=""),
     *               @OA\Property(property="isDeviceAffected", type="string", default="false", example=""),
     *               @OA\Property(property="barCode", type="string", default="", example=""),
     *               @OA\Property(
     *                      property="image",
     *                      type="array",
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003"),
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003")
     *               ),
     *               @OA\Property(property="locationImage", type="string", default="", example=""),
     *               @OA\Property(property="preferredCompany", type="string", default="", example=""),
     *               @OA\Property(property="apartment", type="string", default="", example=""),
     *          )
     *       )
     *     ),
     *  @OA\Response(
     *     response=200,
     *     description="Returns success message after editing ticket",
     *  ),
     *  @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     *  ),
     *  @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     *  ),
     *  @OA\Tag(name="Ticket")
     * )
     * @param Request $request
     * @param string ticketId
     * @param GeneralUtility $generalUtility
     * @param DamageService $damageService
     * @param LoggerInterface $requestLogger
     * @return View
     * @Route("/edit/{ticketId}", name="balu_ticket_edit", methods={"PUT"})
     */
    public function edit(Request $request, string $ticketId, GeneralUtility $generalUtility, DamageService $damageService, LoggerInterface $requestLogger): View
    {
        $em = $this->doctrine->getManager();
        $damage = $damageService->validateAndGetDamageObject($ticketId);
        $currentCategory = ($damage->getIssueType() instanceof Category) ? $damage->getIssueType()->getIdentifier() : null;
        $form = $this->createNamedForm(DamageType::class, $damage, $damageService->getFormOptions($request, $this->currentRole, $this->getUser()));
        $form->submit($request->request->all(), false);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->beginTransaction();
            try {
                $damageService->processDamage($request, $damage, $this->getUser(), $this->currentRole, true, $currentCategory);
                $em->flush();
                $em->commit();
                $data = $generalUtility->handleSuccessResponse('editSuccess',
                    $damageService->generateDamageDetails($damage, $request, $this->getUser(), true, $this->currentRole));
            } catch (InvalidArgumentException | UnsupportedUserException | AccessDeniedException | \Exception $e) {
                $curDate = new \DateTime();
                $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
                $em->rollBack();
                $data = $generalUtility->handleFailedResponse($e->getMessage());
            }
        } else {
            $data = $generalUtility->handleFailedResponse('formError', 400, $this->getErrorsFromForm($form));
        }

        return $this->response($data);
    }

    /**
     * API end point to get details of a ticket
     *
     *
     * # Request
     *
     *
     * ## Success response ##
     *
     *      {
     *          "currentRole": "object_owner",
     *          "data": {
     *              "ticketNumber": "#393",
     *              "publicId": "1ecfc32a-b80f-6214-a7be-0242ac130003",
     *              "status": "OBJECT_OWNER_CREATE_DAMAGE",
     *              "title": "Success ticket 2",
     *              "apartmentName": "rich",
     *              "propertyName": "test address",
     *              "address": {
     *                  "streetName": "Street name",
     *                  "streetNumber": "Keller",
     *                  "postalCode": "20154",
     *                  "city": "Hykon",
     *                  "country": "India",
     *                  "countryCode": "IN",
     *                  "latitude": "12.2221",
     *                  "longitude": "11.11002"
     *                },
     *              "createdAt": "2022-07-05 07:18",
     *              "updatedAt": "2022-07-05 07:18",
     *              "reportedBy": {
     *                 "publicId": "1ecfc32a-b80f-6214-a22e-0242ac130003",
     *                 "firstName": "f name",
     *                 "lastName": "l name"
     *              },
     *              "description": "desc10",
     *              "isDeviceAffected": true,
     *              "propertyId": "1ece2575-db82-69ca-ged9-0242ac170003",
     *              "apartmentId": "1ece2575-db82-69ca-aed8-0242ac170003",
     *              "barCode": "12211-1212312-31231231-1231",
     *              "preferredCompany": {
     *              "publicId": "1ec7c798-9c3f-66aa-8171-00155d01d845",
     *              "name": "company",
     *              "address": {
     *                    "street": "Street name",
     *                    "streetNumber": "Street number",
     *                    "city": "city name",
     *                    "country": "India",
     *                    "countryCode": "IN",
     *                    "zipCode": "201212",
     *                    "phone": "0123456789"
     *                },
     *                "email": "test.company@yopmail.com"
     *               },
     *             "companyAssignedBy": {
     *                   "publicId": "1ec8e179-1f95-63ba-8235-00155d01d845",
     *                   "firstName": "f name",
     *                   "lastName": "l name",
     *                   "role": "owner"
     *              },
     *             "requestedCompanyDetails": [
     *                           {
     *                               "damage": "1ed77b2d-b99f-67ba-8686-00155d01d845",
     *                               "offer": "d41d8cd9-8f00-b204-e980-0998ecf8427e",
     *                               "request": "1edffb05-8272-69d0-bf27-0242ac170005",
     *                               "firstName": "rewa",
     *                               "lastName": "owner",
     *                               "property": "orewa@yopmail.com",
     *                               "phone": "+919756567453",
     *                               "street": "Frankfurter Allee",
     *                               "streetNumber": "1234",
     *                               "city": "fbhyh",
     *                               "zipCode": "578",
     *                               "country": "Germany",
     *                               "countryCode": "DE",
     *                               "damageImages": [
     *                               {
     *                                   "publicId": "1ed4ae05-98c6-6b94-888b-00155d01d845",
     *                                   "originalName": "1ed4ae04-8517-6cc0-ad61-00155d01d845",
     *                                   "path": "http://localhost:8002/var/www/html/API_BALU2_TEST/public/files/property/folder-16656495466347cb8a43f2c/folder-16656498046347cc8cc4410/tickets/folder-16656562956347e5e73e56b/IMG-20221013-WA0058.jpg",
     *                                   "filePath": "/var/www/html/API_BALU2_TEST/public/files/property/folder-16656495466347cb8a43f2c/folder-16656498046347cc8cc4410/tickets/folder-16656562956347e5e73e56b/IMG-20221013-WA0058.jpg",
     *                                   "isPrivate": "public",
     *                                   "mimeType": "image/jpeg",
     *                                   "size": 16408.0,
     *                                   "folder": "1ed4ae05-9788-6c96-a040-00155d01d845",
     *                                   "updatedAt": "2023-05-13T15:48:15+00:00",
     *                                   "type": "photos",
     *                                       "thumbnails": {
     *                                       "image_345X180": "http://localhost:8002/var/www/html/API_BALU2_TEST/public/files/property/folder-16656495466347cb8a43f2c/folder-16656498046347cc8cc4410/tickets/folder-16656562956347e5e73e56b/345-180-IMG-20221013-WA0058.jpg",
     *                                       "image_50X50": "http://localhost:8002/var/www/html/API_BALU2_TEST/public/files/property/folder-16656495466347cb8a43f2c/folder-16656498046347cc8cc4410/tickets/folder-16656562956347e5e73e56b/50-50-IMG-20221013-WA0058.jpg",
     *                                       "image_40X40": "http://localhost:8002/var/www/html/API_BALU2_TEST/public/files/property/folder-16656495466347cb8a43f2c/folder-16656498046347cc8cc4410/tickets/folder-16656562956347e5e73e56b/40-40-IMG-20221013-WA0058.jpg",
     *                                       "image_130X130": "http://localhost:8002/var/www/html/API_BALU2_TEST/public/files/property/folder-16656495466347cb8a43f2c/folder-16656498046347cc8cc4410/tickets/folder-16656562956347e5e73e56b/130-130-IMG-20221013-WA0058.jpg",
     *                                       "image_90X90": "http://localhost:8002/var/www/html/API_BALU2_TEST/public/files/property/folder-16656495466347cb8a43f2c/folder-16656498046347cc8cc4410/tickets/folder-16656562956347e5e73e56b/90-90-IMG-20221013-WA0058.jpg",
     *                                       "image_544X450": "http://localhost:8002/var/www/html/API_BALU2_TEST/public/files/property/folder-16656495466347cb8a43f2c/folder-16656498046347cc8cc4410/tickets/folder-16656562956347e5e73e56b/544-450-IMG-20221013-WA0058.jpg"
     *                                   }
     *                               }
     *                           ],
     *                           "amount": 300.0,
     *                           "priceSplit": {
     *                              "personal": 100,
     *                              "material": 200
     *                            },
     *                           "accepted": false
     *                           },
     *               ],
     *              "originalLocationImages": [
     *              {
     *                   "identifier": 86,
     *                  "publicId": "1ed4eb78-f4a7-60e8-bda9-00155d01d845",
     *                  "originalName": "pic-1666078538634e574ae6eef.jfif",
     *                  "path": "http://localhost:8002/files/property/folder-1666078468634e5704af113/folder-1666078580634e5774a71db/floorPlan/pic-1666078538634e574ae6eef.jfif",
     *                  "displayName": "pic",
     *                  "type": "floorPlan",
     *                  "filePath": "/var/www/html/API_BALU2_TEST/public/files/property/folder-1666078468634e5704af113/folder-1666078580634e5774a71db/floorPlan/pic-1666078538634e574ae6eef.jfif",
     *                  "isPrivate": "public",
     *                  "mimeType": "image/jpeg",
     *                  "size": 5048.0,
     *                  "folder": "1ed4eb78-f336-6b00-bba7-00155d01d845",
     *                  "thumbnails": {
     *                      "image_345X180": "http://localhost:8002/files/property/folder-1666078468634e5704af113/folder-1666078580634e5774a71db/floorPlan/345-180-pic-1666078538634e574ae6eef.jfif",
     *                      "image_50X50": "http://localhost:8002/files/property/folder-1666078468634e5704af113/folder-1666078580634e5774a71db/floorPlan/50-50-pic-1666078538634e574ae6eef.jfif",
     *                      "image_40X40": "http://localhost:8002/files/property/folder-1666078468634e5704af113/folder-1666078580634e5774a71db/floorPlan/40-40-pic-1666078538634e574ae6eef.jfif",
     *                      "image_130X130": "http://localhost:8002/files/property/folder-1666078468634e5704af113/folder-1666078580634e5774a71db/floorPlan/130-130-pic-1666078538634e574ae6eef.jfif",
     *                      "image_90X90": "http://localhost:8002/files/property/folder-1666078468634e5704af113/folder-1666078580634e5774a71db/floorPlan/90-90-pic-1666078538634e574ae6eef.jfif",
     *                      "image_544X450": "http://localhost:8002/files/property/folder-1666078468634e5704af113/folder-1666078580634e5774a71db/floorPlan/544-450-pic-1666078538634e574ae6eef.jfif"
     *                  }
     *              }
     *              ],
     *              "forwardedToCompanyWithOffer": false,
     *              "assignedCompany": {
     *              "publicId": "1ec7c798-9c3f-66aa-8171-00155d01d845",
     *              "name": "company",
     *              "address": {
     *                    "street": "Street name",
     *                    "streetNumber": "Street number",
     *                    "city": "city name",
     *                    "country": "India",
     *                    "countryCode": "IN",
     *                    "zipCode": "201212",
     *                    "phone": "0123456789"
     *                },
     *                "email": "test.company@yopmail.com"
     *               },
     *              "damageImages": [
     *                 {
     *                     "publicId": "1ecfc32a-e36f-6d96-998f-0242ac130003",
     *                     "originalName": "1ecfc321-122e-6b00-9e15-0242ac130003",
     *                     "path": "http://localhost:8001/files/property/folder-165416134862987fc407e88/folder-16541625406298846cd9fa5/damages/folder-165700551362c3e5c9e2284/screenshot20220614at111055am-165700525062c3e4c286583.png",
     *                     "displayName": "1ecfc321-122e-6b00-9e15-0242ac130003",
     *                     "filePath": "/var/www/app/balu/files/property/folder-165416134862987fc407e88/folder-16541625406298846cd9fa5/damages/folder-165700551362c3e5c9e2284/screenshot20220614at111055am-165700525062c3e4c286583.png",
     *                     "isPrivate": "public",
     *                     "mimeType": "image/png",
     *                     "size": 35928.0,
     *                     "folder": "1ecf6cc0-00a5-677e-878d-0242ac130003",
     *                     "type": "damageImages"
     *                 }
     *              ],
     *              "locationImage": {
     *                 "publicId": "1ecfc32a-e797-65e0-832d-0242ac130003",
     *                 "originalName": "1ecfc321-558e-658a-b2ce-0242ac130003",
     *                 "path": "http://localhost:8001/files/property/folder-165416134862987fc407e88/folder-16541625406298846cd9fa5/damages/folder-165700551362c3e5c9e2284/screenshot20220614at111055am-165700525762c3e4c9c678f.png",
     *                 "displayName": "1ecfc321-558e-658a-b2ce-0242ac130003",
     *                 "filePath": "/var/www/app/balu/files/property/folder-165416134862987fc407e88/folder-16541625406298846cd9fa5/damages/folder-165700551362c3e5c9e2284/screenshot20220614at111055am-165700525762c3e4c9c678f.png",
     *                 "isPrivate": "public",
     *                 "mimeType": "image/png",
     *                 "size": 35928.0,
     *                 "folder": "1ecf6cc0-00a5-677e-878d-0242ac130003",
     *                 "type": "locationImages"
     *              },
     *              "signature": {
     *                 "publicId": "1ecfc32a-e797-65e0-832d-0242ac130003",
     *                 "originalName": "1ecfc321-558e-658a-b2ce-0242ac130003",
     *                 "path": "http://localhost:8001/files/property/folder-165416134862987fc407e88/folder-16541625406298846cd9fa5/damages/folder-165700551362c3e5c9e2284/screenshot20220614at111055am-165700525762c3e4c9c678f.png",
     *                 "displayName": "1ecfc321-558e-658a-b2ce-0242ac130003",
     *                 "filePath": "/var/www/app/balu/files/property/folder-165416134862987fc407e88/folder-16541625406298846cd9fa5/damages/folder-165700551362c3e5c9e2284/screenshot20220614at111055am-165700525762c3e4c9c678f.png",
     *                 "isPrivate": "public",
     *                 "mimeType": "image/png",
     *                 "size": 35928.0,
     *                 "folder": "1ecf6cc0-00a5-677e-878d-0242ac130003",
     *                 "type": "locationImages"
     *              },
     *             "tenants": [
     *                  {
     *                      "firstName": "Tenant",
     *                      "lastName": "Lname",
     *                      "email": "tenant@@yopmail.com",
     *                      "address": {
     *                              "street": "Street name",
     *                              "streetNumber": "Street number",
     *                              "city": "city name",
     *                              "country": "India",
     *                              "countryCode": "IN",
     *                              "zipCode": "201212",
     *                              "phone": "0123456789"
     *                      }
     *                  }
     *              ],
     *              "owner": {
     *                  "firstName": "f name",
     *                  "lastName": "l name",
     *                  "email": "admin01@yopmail.com",
     *                  "address": {
     *                      "street": "Bern-Zürichstrasse",
     *                      "streetNumber": "123",
     *                      "city": "Langenthal",
     *                      "country": "Switzerland",
     *                      "countryCode": "CH",
     *                      "zipCode": "123",
     *                      "phone": "+41343434343",
     *                      "landLine": "+41343434343"
     *                  }
     *              },
     *              "admin": {
     *                  "firstName": "Properrty admin",
     *                  "lastName": "Neff",
     *                  "email": "propertyadmin@yopmail.com",
     *                  "address": {
     *                      "street": "Bern-Zürichstrasse",
     *                      "streetNumber": "123",
     *                      "city": "Langenthal",
     *                      "country": "Switzerland",
     *                      "countryCode": "CH",
     *                      "zipCode": "123",
     *                      "phone": "+41343434343",
     *                      "landLine": "+41343434343"
     *                  }
     *              },
     *              "offer": {
     *                  "publicId": "1ed0d7ff-acb8-6718-9bae-0242ac130003"
     *              },
     *              "defect": [
     *                  {
     *                      "defectNumber": "#1",
     *                      "publicId": "1ed13ba1-d2fc-6acc-a552-0242ac130003",
     *                      "title": "new defect",
     *                      "description": "desc",
     *                      "attachent": {
     *                          "publicId": "1ed13bc6-e6dd-66f2-a5d1-0242ac130003",
     *                          "originalName": "any-165959355462eb63526a40e.png",
     *                          "path": "http://localhost:8001/files/property/folder-165416134862987fc407e88/folder-16541625406298846cd9fa5/damages/folder-165958877762eb50a93d3ed/any-165959355462eb63526a40e.png",
     *                          "displayName": "any-165959355462eb63526a40e.png",
     *                          "filePath": "/var/www/app/balu/files/property/folder-165416134862987fc407e88/folder-16541625406298846cd9fa5/damages/folder-165958877762eb50a93d3ed/any-165959355462eb63526a40e.png",
     *                          "isPrivate": "public",
     *                          "mimeType": "image/png",
     *                          "size": 53912.0,
     *                          "folder": "1ecf6cc0-00a5-677e-878d-0242ac130003",
     *                          "type": "defect"
     *                      }
     *                  }
     *              ],
     *               "rating": {
     *                   "publicId": "1ed13c6d-37b6-66ae-8073-0242ac130003",
     *                   "createdOn": "2022-08-04T07:26:59+00:00",
     *                    "ratedBy": {
     *                        "firstName": "f name",
     *                        "lastName": "l name",
     *                        "email": "admin01@yopmail.com"
     *                    },
     *                    "company": {
     *                        "firstName": "Fn",
     *                        "lastName": "Ln",
     *                        "email": "test.company@yopmail.com"
     *                    },
     *                     "rating": 5
     *             }
     *          },
     *          "error": false,
     *          "message": "success"
     *      }
     * @Operation(
     *      tags={"Ticket"},
     *      summary="API end point get details of a ticket",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="ticketId", type="string", default="", example="1ecdb203-7397-6490-b46f-0242ac1b0004"),
     *          )
     *       )
     *     ),
     *  @OA\Response(
     *     response=200,
     *     description="Returns ticket details",
     *  ),
     *  @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     *  ),
     *  @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     *  ),
     *  @OA\Tag(name="Ticket")
     * )
     * @param string $ticketId
     * @param GeneralUtility $generalUtility
     * @param DamageService $damageService
     * @param Request $request
     * @param LoggerInterface $requestLogger
     * @return View
     * @Route("/details/{ticketId}", name="balu_ticket_details", methods={"GET"})
     */
    public function details(string $ticketId, GeneralUtility $generalUtility, DamageService $damageService,
                            Request $request, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        try {
            $data = $generalUtility->handleSuccessResponse('success',
                $damageService->generateDamageDetails($damageService->validateAndGetDamageObject($ticketId),
                    $request, $this->getUser(), false, $this->currentRole)
            );
        } catch (ResourceNotFoundException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to get damage list.
     *
     * # Request
     * System expects filters as a path parameter.
     *
     * # Response
     * ## Success response ##
     *       {
     *           "data": [
     *               {
     *                   "ticketNumber": "#393",
     *                   "publicId": "1ecf6e24-70f8-6822-983a-0242ac130003",
     *                   "title": "New damage",
     *                   "status": "OWNER_SEND_TO_COMPANY_WITHOUT_OFFER",
     *                   "apartmentName": "Ap Example",
     *                   "propertyName": "Example address",
     *                   "createdAt": "2022-06-28 13:00",
     *                   "updatedAt": "2022-06-28 16:00",
     *                   "reportedBy": {
     *                       "publicId": "1ecfc32a-b80f-6214-a22e-0242ac130003",
     *                       "firstName": "f name",
     *                       "lastName": "l name"
     *                   },
     *                   "preferredCompany": "company",
     *                   "companyName": "company",
     *                   "companyAssignedBy": {
     *                      "firstName": "f name",
     *                      "lastName": "l name",
     *                      "role: "owner"
     *
     *                   },
     *                  "isRead": false
     *               },
     *               {
     *                   "ticketNumber": "#395",
     *                   "publicId": "1ecf6e15-6796-61bc-a329-0242ac130003",
     *                   "status": "TENANT_CREATE_DAMAGE",
     *                   "title": "Old Damage",
     *                   "apartmentName": "Ab Example",
     *                   "propertyName": "Example address",
     *                   "createdAt": "2022-06-28 12:53",
     *                   "reportedBy": {
     *                       "publicId": "1ecfc32a-b80f-6214-a22e-0242ac130003",
     *                       "firstName": "f name",
     *                       "lastName": "l name"
     *                   },
     *                  "isRead": false
     *               }
     *           ],
     *           "error": false,
     *           "message": "listSuccess"
     *       }
     * ## Failed response ##
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Fetching Damage list failed."
     *      }
     * @Route("/list", name="balu_ticket_list", methods={"GET"})
     * @Operation(
     *      tags={"Ticket"},
     *      summary="API end point to get damage list.",
     *      @Security(name="Bearer"),
     *      @OA\Parameter(
     *      name="limit",
     *      in="query",
     *      description="Total number of results to be fetched",
     *      @OA\Schema(type="integer")
     *     ),
     *      @OA\Parameter(
     *      name="offset",
     *      in="query",
     *      description="Page",
     *      @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *      name="filter[text]",
     *      in="query",
     *      description="Text can be ticket ID or title",
     *      @OA\Schema(type="string")
     *     ),     *
     *     @OA\Parameter(
     *      name="filter[status]",
     *      in="query",
     *      description="Status can be open/closed or any other damage status",
     *      @OA\Schema(type="string")
     *     ),
     *      @OA\Parameter(
     *      name="filter[apartment]",
     *      in="query",
     *      @OA\Schema(type="string")
     *     ),
     *      @OA\Parameter(
     *      name="filter[property]",
     *      in="query",
     *      @OA\Schema(type="string")
     *     ),
     *      @OA\Parameter(
     *      name="filter[assignedTo]",
     *      in="query",
     *      @OA\Schema(type="string")
     *     ),
     *      @OA\Parameter(
     *      name="filter[preferredCompany]",
     *      in="query",
     *      @OA\Schema(type="string")
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
     * )
     *
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param DamageService $damageService
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function list(Request $request, GeneralUtility $generalUtility, DamageService $damageService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        try {
            $data = $generalUtility->handleSuccessResponse('listSuccess', $damageService->getDamageList($request, $this->getUser(), $this->currentRole));
        } catch (ResourceNotFoundException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to update ticket status
     * Request to update the issueType
     *          {
     *                 "ticket":  "1ece26e7-5826-6cac-af58-0242ac170003",
     *                 "issueType" : "1ece26e7-5826-6cac-af58-0242ac170003"
     *          }
     * # Request for generic status change
     *          {
     *                 "ticket":  "1ece26e7-5826-6cac-af58-0242ac170003",
     *                 "status" : "OWNER_ACCEPTS_THE_OFFER",
     *                 "currentStatus" : "COMPANY_GIVE_OFFER_TO_OWNER"
     *          }
     * # Request to reject a damage
     *          {
     *                 "ticket":  "1ece26e7-5826-6cac-af58-0242ac170003",
     *                 "status" : "OWNER_REJECT_DAMAGE",
     *                 "currentStatus" : "TENANT_CREATE_DAMAGE",
     *                 "company" : "1ec8e31c-6ae7-6c38-8fa2-00155d01d845",
     *                 "comment" : "not a damage"
     *          }
     * # Request to update damage request status
     *          {
     *                 "ticket":  "1ece26e7-5826-6cac-af58-0242ac170003",
     *                 "status" : "OWNER_REJECT_DAMAGE",
     *                 "currentStatus" : "TENANT_CREATE_DAMAGE",
     *                 "damageRequest" : "1ec8e31c-6ae7-6c38-8fa2-00155d01d845",
     *                 "damageRequestStatus" : "COMPANY_GIVE_OFFER_TO_OWNER"
     *          }
     * # Request to schedule a date
     *          {
     *                 "ticket":  "1ed12348-7914-69a4-8396-0242ac130003",
     *                 "status" : "COMPANY_SCHEDULE_DATE",
     *                 "currentStatus" : "OWNER_ACCEPTS_THE_OFFER",
     *                 "date": "2022-01-23",
     *                 "time": "10:35"
     *          }
     *
     *  # Request to confirm repair
     *          {
     *                 "ticket":  "1ed12348-7914-69a4-8396-0242ac130003",
     *                 "status" : "REPAIR_CONFIRMED",
     *                 "currentStatus" : "OWNER_ACCEPTS_DATE" ,
     *                 "withSignature":true,
     *                 "signature": "1ed12522-5bf3-683a-92a4-0242ac130003"
     *          }
     *
     *  # Request to raise defect
     *          {
     *                 "ticket":  "1ed12348-7914-69a4-8396-0242ac130003",
     *                 "status" : "DEFECT_RAISED",
     *                 "currentStatus" : "REPAIR_CONFIRMED" ,
     *                 "title": "new defect ",
     *                 "description": "new defect example"
     *                 "attachment": ["1ed12522-5bf3-683a-92a4-0242ac130003"]
     *          }     *
     *
     * # Status can be:
     *                 OWNER_REJECT_DAMAGE
     *                 OWNER_SEND_TO_COMPANY_WITH_OFFER
     *                 OWNER_SEND_TO_COMPANY_WITHOUT_OFFER
     *                 TENANT_SEND_TO_COMPANY_WITH_OFFER
     *                 TENANT_SEND_TO_COMPANY_WITHOUT_OFFER
     *                 TENANT_CLOSE_THE_DAMAGE
     *                 COMPANY_ACCEPTS_DAMAGE_WITH_OFFER
     *                 COMPANY_ACCEPTS_DAMAGE_WITHOUT_OFFER
     *                 COMPANY_REJECT_THE_DAMAGE
     *                 COMPANY_GIVE_OFFER_TO_TENANT
     *                 COMPANY_GIVE_OFFER_TO_OWNER
     *                 TENANT_ACCEPTS_THE_OFFER
     *                 TENANT_REJECTS_THE_OFFER
     *                 OWNER_ACCEPTS_THE_OFFER
     *                 OWNER_REJECTS_THE_OFFER
     *                 COMPANY_SCHEDULE_DATE
     *                 TENANT_ACCEPTS_DATE
     *                 TENANT_REJECTS_DATE
     *                 OWNER_ACCEPTS_DATE
     *                 OWNER_REJECTS_DATE
     *                 OWNER_CLOSE_THE_DAMAGE
     *                 REPAIR_CONFIRMED
     *                 DEFECT_RAISED
     * ## Success response ##     *
     *           {
     *                "currentRole": "owner",
     *                "data": "No data provided",
     *                "error": false,
     *                "message": "Update Success"
     *           }
     * @Operation(
     *      tags={"Ticket"},
     *      summary="API end point to change ticket status",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="ticketId", type="string", default="", example=""),
     *               @OA\Property(property="status", type="string", default="", example=""),
     *               @OA\Property(property="currentStatus", type="string", default="", example=""),
     *               @OA\Property(property="company", type="string", default="", example=""),
     *               @OA\Property(property="comment", type="string", default="", example=""),
     *               @OA\Property(property="offer", type="string", default="", example=""),
     *          )
     *       )
     *     ),
     *  @OA\Response(
     *     response=200,
     *     description="Returns success response",
     *  ),
     *  @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     *  ),
     *  @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     *  ),
     *  @OA\Tag(name="Ticket")
     * )
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param DamageService $damageService
     * @param LoggerInterface $requestLogger
     * @return View
     * @Route("/update", name="balu_ticket_update", methods={"PATCH"})
     */
    public function update(Request $request, GeneralUtility $generalUtility, DamageService $damageService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $damage = new Damage();
        $form = $this->createNamedForm(DamageStatusType::class, $damage, $damageService->getFormOptions($request, $this->currentRole, $this->getUser()));
        $form->submit($request->request->all());
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->beginTransaction();
            try {
                $damageService->updateStatus($request, $this->getUser(), $this->currentRole);
                $em->commit();
                $data = $generalUtility->handleSuccessResponse('updatedSuccessfully');
            } catch (InvalidArgumentException | UnsupportedUserException | AccessDeniedException | \Exception $e) {
                $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
                $em->rollBack();
                $data = $generalUtility->handleFailedResponse('updateFailed');
            }
        } else {
            $data = $generalUtility->handleFailedResponse('formError', 400, $this->getErrorsFromForm($form));
        }

        return $this->response($data);
    }


    /**
     * API end point to get list of all users related to a ticket
     *
     *
     * # Request
     * System expects ticket ID as a Path parameter.
     *
     * ## Success response ##
     *
     *
     *       {
     *           "data": [
     *               {
     *                   "publicId": "1ece257d-4781-6bb0-a5c1-0242ac170003",
     *                   "firstName": "Tenant",
     *                   "lastName": "Example",
     *                   "companyName": "Company name",
     *                   "email": "tenant@example.com",
     *                   "deviceId": ["1ec7c7900155d01d845"],
     *                   "apartment": {
     *                       "publicId": "1ece2575-db82-69ca-aed8-0242ac170003",
     *                       "name": "Apartment 1"
     *                   }
     *               },
     *               {
     *                   "publicId": "1ec7c797-dc02-6a18-a18b-00155d01d845",
     *                   "firstName": "Janitor",
     *                   "lastName": "Example",
     *                   "email": "marcel-dellner@gmx.net",
     *                   "deviceId": ["1ec7c7900155d01h8447","1ec7c7900155d01d845"],
     *                   "apartment": {
     *                       "publicId": "1ece2575-db82-69ca-aed8-0242ac170003",
     *                       "name": "Apartment 2"
     *                   }
     *               },
     *               {
     *                   "publicId": "1ec7c797-e4f3-6e4c-bbdf-00155d01d845",
     *                   "firstName": "David",
     *                   "lastName": "Neff",
     *                   "companyName": "Eigenmann AG",
     *                   "email": "propertyadmin@example.com",
     *                   "deviceId": ["1ec7c7900155d01d244"],
     *                   "apartment": {
     *                       "publicId": "1ece2575-db82-69ca-aed8-0242ac170003",
     *                       "name": "Apartment 5"
     *                   }
     *               },
     *               {
     *                   "publicId": "1ec8e27e-3f54-6538-9ba4-00155d01d845",
     *                   "firstName": "Robert",
     *                   "lastName": "Abc",
     *                   "companyName": "we",
     *                   "email": "onwer@example.com",
     *                   "deviceId": ["1ec734155d01d234"],
     *                   "apartment": {
     *                       "publicId": "1ece2575-db82-69ca-aed8-0242ac170003",
     *                       "name": "Apartment 5"
     *                   }
     *               }
     *           ],
     *           "error": false,
     *           "message": "success"
     *       }
     *
     * @Operation(
     *      tags={"Ticket"},
     *      summary="API end point get all users related to a ticket",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="ticketId", type="string", default="", example="1ecdb203-7397-6490-b46f-0242ac1b0004"),
     *          )
     *       )
     *     ),
     *  @OA\Response(
     *     response=200,
     *     description="Returns list of all users related to a ticket",
     *  ),
     *  @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     *  ),
     *  @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     *  ),
     *  @OA\Tag(name="Ticket")
     * )
     * @param string $ticketId
     * @param GeneralUtility $generalUtility
     * @param DamageService $damageService
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @return View
     * @Route("/users/{ticketId}", name="balu_ticket_users", methods={"GET"})
     */
    public function users(string $ticketId, GeneralUtility $generalUtility, DamageService $damageService, LoggerInterface $requestLogger, Request $request): View
    {
        $curDate = new \DateTime('now');
        try {
            $data = $generalUtility->handleSuccessResponse('success', $damageService->getTicketUsers($damageService->validateAndGetDamageObject($ticketId)));
        } catch (ResourceNotFoundException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to delete damage.
     *
     * # Request
     * In url, system expects damage uuid.
     * # Response
     * ## Success response ##
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "formError"
     *       }
     * @Route("/delete/{ticketId}", name="balu_delete_damage", methods={"DELETE"})
     * @Operation(
     *      tags={"Ticket"},
     *      summary="API end point to to delete damage.",
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
     * @param string $ticketId
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param DamageService $damageService
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function delete(string $ticketId, Request $request, GeneralUtility $generalUtility, DamageService $damageService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        try {
            $damage = $damageService->validateAndGetDamageObject($ticketId);
            $damageService->validatePermission($request, $this->currentRole, $this->getUser(), $damage->getApartment());
            $damage->setDeleted(true);
            $em->flush();
            $data = $generalUtility->handleSuccessResponse('damageDeleted');
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to get a filtered damage list using free text search.
     *
     * # Request
     * System expects filters as a path parameter.
     *
     * # Response
     * ## Success response ##
     *       {
     *           "data": [
     *               {
     *                   "ticketNumber": "#393",
     *                   "publicId": "1ecf6e24-70f8-6822-983a-0242ac130003",
     *                   "title": "New damage",
     *                   "status": "OWNER_SEND_TO_COMPANY_WITHOUT_OFFER",
     *                   "apartmentName": "Ap Example",
     *                   "propertyName": "Example address",
     *                   "createdAt": "2022-06-28 13:00",
     *                   "updatedAt": "2022-06-28 16:00",
     *                   "reportedBy": {
     *                       "publicId": "1ecfc32a-b80f-6214-a22e-0242ac130003",
     *                       "firstName": "f name",
     *                       "lastName": "l name"
     *                   },
     *                   "preferredCompany": "company",
     *                   "companyName": "company",
     *                   "companyAssignedBy": {
     *                      "firstName": "f name",
     *                      "lastName": "l name",
     *                      "role: "owner"
     *
     *                   },
     *                  "isRead": false
     *               },
     *               {
     *                   "ticketNumber": "#395",
     *                   "publicId": "1ecf6e15-6796-61bc-a329-0242ac130003",
     *                   "status": "TENANT_CREATE_DAMAGE",
     *                   "title": "Old Damage",
     *                   "apartmentName": "Ab Example",
     *                   "propertyName": "Example address",
     *                   "createdAt": "2022-06-28 12:53",
     *                   "reportedBy": {
     *                       "publicId": "1ecfc32a-b80f-6214-a22e-0242ac130003",
     *                       "firstName": "f name",
     *                       "lastName": "l name"
     *                   },
     *                  "isRead": false
     *               }
     *           ],
     *           "error": false,
     *           "message": "listSuccess"
     *       }
     * ## Failed response ##
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "Fetching Damage list failed."
     *      }
     * @Route("/search", name="balu_ticket_search", methods={"POST"})
     * @Operation(
     *      tags={"Ticket"},
     *      summary="API end point to get damage list.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="limit", type="integer", default="", example="10"),
     *               @OA\Property(property="offset", type="integer", default="", example="1"),
     *               @OA\Property(property="text", type="string", default="", example="test"),
     *               @OA\Property(property="status", type="string", default="", example="open"),
     *               @OA\Property(property="property", type="string", default="", example="1ecf6e15-6796-61bc-a329-0242ac130003"),
     *          )
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
     * )
     *
     * @param Request $request
     * @param GeneralUtility $generalUtility
     * @param DamageService $damageService
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function searchList(Request $request, GeneralUtility $generalUtility, DamageService $damageService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        try {
            $data = $generalUtility->handleSuccessResponse('listSuccess', $damageService->getFilteredDamageList($request, $this->getUser(), $this->currentRole));
        } catch (ResourceNotFoundException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to get location details of a ticket
     *
     *
     * # Request
     *
     *
     * ## Success response ##
     *
     *      {
     *          "currentRole": "object_owner",
     *          "data": {
     *              "locationImage": {
     *                 "publicId": "1ecfc32a-e797-65e0-832d-0242ac130003",
     *                 "originalName": "1ecfc321-558e-658a-b2ce-0242ac130003",
     *                 "path": "http://localhost:8001/files/property/folder-165416134862987fc407e88/folder-16541625406298846cd9fa5/damages/folder-165700551362c3e5c9e2284/screenshot20220614at111055am-165700525762c3e4c9c678f.png",
     *                 "displayName": "1ecfc321-558e-658a-b2ce-0242ac130003",
     *                 "filePath": "/var/www/app/balu/files/property/folder-165416134862987fc407e88/folder-16541625406298846cd9fa5/damages/folder-165700551362c3e5c9e2284/screenshot20220614at111055am-165700525762c3e4c9c678f.png",
     *                 "isPrivate": "public",
     *                 "mimeType": "image/png",
     *                 "size": 35928.0,
     *                 "folder": "1ecf6cc0-00a5-677e-878d-0242ac130003",
     *                 "type": "locationImages"
     *              }
     *          },
     *          "error": false,
     *          "message": "success"
     *      }
     * @Operation(
     *      tags={"Ticket"},
     *      summary="API end point get details of a ticket",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="ticketId", type="string", default="", example="1ecdb203-7397-6490-b46f-0242ac1b0004"),
     *          )
     *       )
     *     ),
     *  @OA\Response(
     *     response=200,
     *     description="Returns ticket details",
     *  ),
     *  @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     *  ),
     *  @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     *  ),
     *  @OA\Tag(name="Ticket")
     * )
     * @param string $ticketId
     * @param GeneralUtility $generalUtility
     * @param DamageService $damageService
     * @param Request $request
     * @return View
     * @Route("/location-image/{ticketId}", name="balu_location_details", methods={"GET"})
     */
    public function getLocationImage(string $ticketId, GeneralUtility $generalUtility, DamageService $damageService, Request $request, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        try {
            $data = $generalUtility->handleSuccessResponse('success', $damageService->getTicketLocationDetails($damageService->validateAndGetDamageObject($ticketId), $request, $this->getUser(), false, $this->currentRole));
        } catch (ResourceNotFoundException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to add offer request.
     *
     * # Request
     * # Response
     * ## Success response ##
     *       {
     *       "data": {
     *       },
     *       "error": false,
     *       "message": "Offer request added successfully"
     *   }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "formError"
     *       }
     * @Operation(
     *      tags={"Ticket"},
     *      summary="API end point to add offer request.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *      @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="damage", type="string", default="", example="1ed4ace5-a5c8-6d5e-9fb6-00155d01d845"),
     *               @OA\Property(
     *                      property="company",
     *                      type="array",
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003"),
     *                      @OA\Items(type="string", example="1eccb6f1-68a5-6fa4-b233-0242ac120003")
     *               ),
     *               @OA\Property(property="requested_date", type="date", default="", example="10-05-2023"),
     *               @OA\Property(property="new_offer_requested_date", type="date", default="", example="10-05-2023"),
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
     * @param LoggerInterface $requestLogger
     * @param GeneralUtility $generalUtility
     * @param DamageService $damageService
     * @param UserPasswordHasherInterface $passwordHasher
     * @param CompanyService $companyService
     * @return View
     * @Route("/offer-request", name="balu_damage_offer_request", methods={"POST"})
     */
    public function offerRequest(Request $request, LoggerInterface $requestLogger, GeneralUtility $generalUtility, DamageService $damageService,
                                 UserPasswordHasherInterface $passwordHasher, CompanyService $companyService): View
    {
        $curDate = new \DateTime('now');
        $damageRequest = new DamageRequest();
        $form = $this->createNamedForm(DamageRequestType::class, $damageRequest);
        $form->submit($request->request->all());
        $data = $generalUtility->handleFailedResponse('formError', 400, $this->getErrorsFromForm($form));
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->beginTransaction();
            try {
                $params = $request->request->all();
                $params = $damageService->checkRequestAlreadyInitiated($params);
                $response = [];
                if (count($params['company']) > 0) {
                    $response = $companyService->saveDamageRequest($params, $passwordHasher, $this->currentRole);
                    $damage = $em->getRepository(Damage::class)->findOneBy(['publicId' => $params['damage']]);
                    $damageService->logDamage($this->getUser(), $damage, null, null, null, $response);
                }
                $data = $generalUtility->handleSuccessResponse('offerRequestAdded', $response);
                $em->commit();
            } catch (ResourceNotFoundException | \Exception $e) {
                $em->rollBack();
                $data = $generalUtility->handleFailedResponse($e->getMessage());
                $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            }
        }

        return $this->response($data);
    }

    /**
     * API end point to get offer request details.
     *
     * # Request
     * # Response
     * ## Success response ##
     *      {
     *           "currentRole": "company",
     *          "data": [
     *              {
     *                  "publicId": "1edfe01b-d245-6426-964e-0242ac130002",
     *                  "damage": {
     *                      "publicId": "1ed77b2d-b99f-67ba-8686-00155d01d845",
     *                      "title": "test98",
     *                      "description": "huhjk",
     *                      "status": "REPAIR_CONFIRMED"
     *                  },
     *                  "company": {
     *                      "publicId": "1ed4acdc-e8ac-662e-a022-00155d01d845",
     *                      "firstName": "rewa",
     *                      "lastName": "owner"
     *                  },
     *                  "createdAt": "2023-05-29T09:18:14+00:00"
     *              },
     *              {
     *                  "publicId": "1edfe0a2-56bc-6476-b1eb-0242ac130002",
     *                  "damage": {
     *                      "publicId": "1ed77b2d-b99f-67ba-8686-00155d01d845",
     *                      "title": "test98",
     *                      "description": "huhjk",
     *                      "status": "REPAIR_CONFIRMED"
     *                  },
     *                  "company": {
     *                      "publicId": "1ed4acdc-e8ac-662e-a022-00155d01d845",
     *                      "firstName": "rewa",
     *                      "lastName": "owner"
     *                  },
     *                  "createdAt": "2023-05-29T10:18:24+00:00"
     *              }
     *              ],
     *          "error": true,
     *          "message": "Data fetched"
     *      }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "formError"
     *       }
     * @Operation(
     *      tags={"Ticket"},
     *      summary="API end point to get offer request details.",
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
     * @param GeneralUtility $generalUtility
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @return View
     *
     * @Route("/offer-details", name="balu_damage_offer_details", methods={"GET"})
     */
    public function getOfferDetailListByCompany(GeneralUtility $generalUtility, LoggerInterface $requestLogger, Request $request): View
    {
        $curDate = new \DateTime('now');
        $user = $this->getUser();
        $data = $generalUtility->handleFailedResponse('formError', 400);
        try {
            if ($this->currentRole == Constants::COMPANY_ROLE) {
                $em = $this->doctrine->getManager();
                $list = $em->getRepository(DamageRequest::class)->getDamageRequestDetails($user);
                return $this->response($generalUtility->handleSuccessResponse('listFetchSuccess', $list));
            }
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            return $this->response($generalUtility->handleFailedResponse($e->getMessage()));
        }
        return $this->response($data);
    }

    /**
     * API end point to add offer request for non registered companies
     *
     * # Request
     * ## Example request for ticket allocation for non registered companies
     *      {
     *          "email" : "guestuser@example.com;guestuser1@example.com;guestuser3@example.com",
     *          "subject": "Ticket allocation request",
     *          "damage": "1eccb6f1-68a5-6fa4-b233-0242ac120003",
     *          "status": "OWNER_SEND_TO_COMPANY_WITHOUT_OFFER",
     *      }
     * # Response
     * ## Success response ##
     *       {
     *       "data": {
     *       },
     *       "error": false,
     *       "message": "Offer request added successfully"
     *   }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "formError"
     *       }
     * @Operation(
     *      tags={"Ticket"},
     *      summary="API end point to add offer request for non registered companies",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *      @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="email", type="string", default="", example="guestuser@example.com;guestuser1@example.com;guestuser3@example.com"),
     *               @OA\Property(property="subject", type="string", default="", example="Ticket allocation request"),
     *               @OA\Property(property="damage", type="string", default="", example="1eccb6f1-68a5-6fa4-b233-0242ac120003")
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
     * @param LoggerInterface $requestLogger
     * @param GeneralUtility $generalUtility
     * @param DamageService $damageService
     * @return View
     * @Route("/offer-request/non-registered-companies", name="balu_damage_offer_request_non_registered_companies", methods={"POST"})
     */
    public function sendDamageOfferRequestEmailToNonRegisteredCompanies(Request $request, LoggerInterface $requestLogger, GeneralUtility $generalUtility,
                                                                        DamageService $damageService): View
    {
        $curDate = new \DateTime('now');
        $damageRequest = new DamageRequest();
        $form = $this->createNamedForm(DamageRequestType::class, $damageRequest);
        $form->submit($request->request->all());
        $data = $generalUtility->handleFailedResponse('formError', 400, $this->getErrorsFromForm($form));
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->beginTransaction();
            try {
                $data = $generalUtility->handleSuccessResponse('offerRequestAdded',
                    $damageService->saveNonRegisteredUsersDamageOfferRequest($request->request->all(), $this->getUser(), $this->currentRole, $this->locale));
                $em->commit();
            } catch (ResourceNotFoundException | \Exception $e) {
                $em->rollBack();
                $data = $generalUtility->handleFailedResponse($e->getMessage());
                $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            }
        }

        return $this->response($data);
    }

    /**
     * API end point get details of a ticket which can be accessible by public
     *
     * ## Success response ##
     *
     *      {
     *          "currentRole": "object_owner",
     *          "data": {
     *              "ticketNumber": "#393",
     *              "publicId": "1ecfc32a-b80f-6214-a7be-0242ac130003",
     *              "status": "OBJECT_OWNER_CREATE_DAMAGE",
     *              "title": "Success ticket 2",
     *              "apartmentName": "rich",
     *              "propertyName": "test address",
     *              "address": {
     *                  "streetName": "Street name",
     *                  "streetNumber": "Keller",
     *                  "postalCode": "20154",
     *                  "city": "Hykon",
     *                  "country": "India",
     *                  "countryCode": "IN",
     *                  "latitude": "12.2221",
     *                  "longitude": "11.11002"
     *                },
     *              "createdAt": "2022-07-05 07:18",
     *              "updatedAt": "2022-07-05 07:18",
     *              "reportedBy": {
     *                 "publicId": "1ecfc32a-b80f-6214-a22e-0242ac130003",
     *                 "firstName": "f name",
     *                 "lastName": "l name"
     *              },
     *              "description": "desc10",
     *              "isDeviceAffected": true,
     *              "propertyId": "1ece2575-db82-69ca-ged9-0242ac170003",
     *              "apartmentId": "1ece2575-db82-69ca-aed8-0242ac170003",
     *              "barCode": "12211-1212312-31231231-1231",
     *              "preferredCompany": {
     *              "publicId": "1ec7c798-9c3f-66aa-8171-00155d01d845",
     *              "name": "company",
     *              "address": {
     *                    "street": "Street name",
     *                    "streetNumber": "Street number",
     *                    "city": "city name",
     *                    "country": "India",
     *                    "countryCode": "IN",
     *                    "zipCode": "201212",
     *                    "phone": "0123456789"
     *                },
     *                "email": "test.company@yopmail.com"
     *               },
     *             "companyAssignedBy": {
     *                   "publicId": "1ec8e179-1f95-63ba-8235-00155d01d845",
     *                   "firstName": "f name",
     *                   "lastName": "l name",
     *                   "role": "owner"
     *              },
     *             "requestedCompanyDetails": [
     *                           {
     *                               "damage": "1ed77b2d-b99f-67ba-8686-00155d01d845",
     *                               "offer": "d41d8cd9-8f00-b204-e980-0998ecf8427e",
     *                               "request": "1edffb05-8272-69d0-bf27-0242ac170005",
     *                               "firstName": "rewa",
     *                               "lastName": "owner",
     *                               "property": "orewa@yopmail.com",
     *                               "phone": "+919756567453",
     *                               "street": "Frankfurter Allee",
     *                               "streetNumber": "1234",
     *                               "city": "fbhyh",
     *                               "zipCode": "578",
     *                               "country": "Germany",
     *                               "countryCode": "DE",
     *                               "damageImages": [
     *                               {
     *                                   "publicId": "1ed4ae05-98c6-6b94-888b-00155d01d845",
     *                                   "originalName": "1ed4ae04-8517-6cc0-ad61-00155d01d845",
     *                                   "path": "http://localhost:8002/var/www/html/API_BALU2_TEST/public/files/property/folder-16656495466347cb8a43f2c/folder-16656498046347cc8cc4410/tickets/folder-16656562956347e5e73e56b/IMG-20221013-WA0058.jpg",
     *                                   "filePath": "/var/www/html/API_BALU2_TEST/public/files/property/folder-16656495466347cb8a43f2c/folder-16656498046347cc8cc4410/tickets/folder-16656562956347e5e73e56b/IMG-20221013-WA0058.jpg",
     *                                   "isPrivate": "public",
     *                                   "mimeType": "image/jpeg",
     *                                   "size": 16408.0,
     *                                   "folder": "1ed4ae05-9788-6c96-a040-00155d01d845",
     *                                   "updatedAt": "2023-05-13T15:48:15+00:00",
     *                                   "type": "photos",
     *                                       "thumbnails": {
     *                                       "image_345X180": "http://localhost:8002/var/www/html/API_BALU2_TEST/public/files/property/folder-16656495466347cb8a43f2c/folder-16656498046347cc8cc4410/tickets/folder-16656562956347e5e73e56b/345-180-IMG-20221013-WA0058.jpg",
     *                                       "image_50X50": "http://localhost:8002/var/www/html/API_BALU2_TEST/public/files/property/folder-16656495466347cb8a43f2c/folder-16656498046347cc8cc4410/tickets/folder-16656562956347e5e73e56b/50-50-IMG-20221013-WA0058.jpg",
     *                                       "image_40X40": "http://localhost:8002/var/www/html/API_BALU2_TEST/public/files/property/folder-16656495466347cb8a43f2c/folder-16656498046347cc8cc4410/tickets/folder-16656562956347e5e73e56b/40-40-IMG-20221013-WA0058.jpg",
     *                                       "image_130X130": "http://localhost:8002/var/www/html/API_BALU2_TEST/public/files/property/folder-16656495466347cb8a43f2c/folder-16656498046347cc8cc4410/tickets/folder-16656562956347e5e73e56b/130-130-IMG-20221013-WA0058.jpg",
     *                                       "image_90X90": "http://localhost:8002/var/www/html/API_BALU2_TEST/public/files/property/folder-16656495466347cb8a43f2c/folder-16656498046347cc8cc4410/tickets/folder-16656562956347e5e73e56b/90-90-IMG-20221013-WA0058.jpg",
     *                                       "image_544X450": "http://localhost:8002/var/www/html/API_BALU2_TEST/public/files/property/folder-16656495466347cb8a43f2c/folder-16656498046347cc8cc4410/tickets/folder-16656562956347e5e73e56b/544-450-IMG-20221013-WA0058.jpg"
     *                                   }
     *                               }
     *                           ],
     *                           "amount": 300.0,
     *                           "priceSplit": {
     *                              "personal": 100,
     *                              "material": 200
     *                            },
     *                           "accepted": false
     *                           },
     *               ],
     *              "originalLocationImages": [
     *              {
     *                   "identifier": 86,
     *                  "publicId": "1ed4eb78-f4a7-60e8-bda9-00155d01d845",
     *                  "originalName": "pic-1666078538634e574ae6eef.jfif",
     *                  "path": "http://localhost:8002/files/property/folder-1666078468634e5704af113/folder-1666078580634e5774a71db/floorPlan/pic-1666078538634e574ae6eef.jfif",
     *                  "displayName": "pic",
     *                  "type": "floorPlan",
     *                  "filePath": "/var/www/html/API_BALU2_TEST/public/files/property/folder-1666078468634e5704af113/folder-1666078580634e5774a71db/floorPlan/pic-1666078538634e574ae6eef.jfif",
     *                  "isPrivate": "public",
     *                  "mimeType": "image/jpeg",
     *                  "size": 5048.0,
     *                  "folder": "1ed4eb78-f336-6b00-bba7-00155d01d845",
     *                  "thumbnails": {
     *                      "image_345X180": "http://localhost:8002/files/property/folder-1666078468634e5704af113/folder-1666078580634e5774a71db/floorPlan/345-180-pic-1666078538634e574ae6eef.jfif",
     *                      "image_50X50": "http://localhost:8002/files/property/folder-1666078468634e5704af113/folder-1666078580634e5774a71db/floorPlan/50-50-pic-1666078538634e574ae6eef.jfif",
     *                      "image_40X40": "http://localhost:8002/files/property/folder-1666078468634e5704af113/folder-1666078580634e5774a71db/floorPlan/40-40-pic-1666078538634e574ae6eef.jfif",
     *                      "image_130X130": "http://localhost:8002/files/property/folder-1666078468634e5704af113/folder-1666078580634e5774a71db/floorPlan/130-130-pic-1666078538634e574ae6eef.jfif",
     *                      "image_90X90": "http://localhost:8002/files/property/folder-1666078468634e5704af113/folder-1666078580634e5774a71db/floorPlan/90-90-pic-1666078538634e574ae6eef.jfif",
     *                      "image_544X450": "http://localhost:8002/files/property/folder-1666078468634e5704af113/folder-1666078580634e5774a71db/floorPlan/544-450-pic-1666078538634e574ae6eef.jfif"
     *                  }
     *              }
     *              ],
     *              "forwardedToCompanyWithOffer": false,
     *              "assignedCompany": {
     *              "publicId": "1ec7c798-9c3f-66aa-8171-00155d01d845",
     *              "name": "company",
     *              "address": {
     *                    "street": "Street name",
     *                    "streetNumber": "Street number",
     *                    "city": "city name",
     *                    "country": "India",
     *                    "countryCode": "IN",
     *                    "zipCode": "201212",
     *                    "phone": "0123456789"
     *                },
     *                "email": "test.company@yopmail.com"
     *               },
     *              "damageImages": [
     *                 {
     *                     "publicId": "1ecfc32a-e36f-6d96-998f-0242ac130003",
     *                     "originalName": "1ecfc321-122e-6b00-9e15-0242ac130003",
     *                     "path": "http://localhost:8001/files/property/folder-165416134862987fc407e88/folder-16541625406298846cd9fa5/damages/folder-165700551362c3e5c9e2284/screenshot20220614at111055am-165700525062c3e4c286583.png",
     *                     "displayName": "1ecfc321-122e-6b00-9e15-0242ac130003",
     *                     "filePath": "/var/www/app/balu/files/property/folder-165416134862987fc407e88/folder-16541625406298846cd9fa5/damages/folder-165700551362c3e5c9e2284/screenshot20220614at111055am-165700525062c3e4c286583.png",
     *                     "isPrivate": "public",
     *                     "mimeType": "image/png",
     *                     "size": 35928.0,
     *                     "folder": "1ecf6cc0-00a5-677e-878d-0242ac130003",
     *                     "type": "damageImages"
     *                 }
     *              ],
     *              "locationImage": {
     *                 "publicId": "1ecfc32a-e797-65e0-832d-0242ac130003",
     *                 "originalName": "1ecfc321-558e-658a-b2ce-0242ac130003",
     *                 "path": "http://localhost:8001/files/property/folder-165416134862987fc407e88/folder-16541625406298846cd9fa5/damages/folder-165700551362c3e5c9e2284/screenshot20220614at111055am-165700525762c3e4c9c678f.png",
     *                 "displayName": "1ecfc321-558e-658a-b2ce-0242ac130003",
     *                 "filePath": "/var/www/app/balu/files/property/folder-165416134862987fc407e88/folder-16541625406298846cd9fa5/damages/folder-165700551362c3e5c9e2284/screenshot20220614at111055am-165700525762c3e4c9c678f.png",
     *                 "isPrivate": "public",
     *                 "mimeType": "image/png",
     *                 "size": 35928.0,
     *                 "folder": "1ecf6cc0-00a5-677e-878d-0242ac130003",
     *                 "type": "locationImages"
     *              },
     *              "signature": {
     *                 "publicId": "1ecfc32a-e797-65e0-832d-0242ac130003",
     *                 "originalName": "1ecfc321-558e-658a-b2ce-0242ac130003",
     *                 "path": "http://localhost:8001/files/property/folder-165416134862987fc407e88/folder-16541625406298846cd9fa5/damages/folder-165700551362c3e5c9e2284/screenshot20220614at111055am-165700525762c3e4c9c678f.png",
     *                 "displayName": "1ecfc321-558e-658a-b2ce-0242ac130003",
     *                 "filePath": "/var/www/app/balu/files/property/folder-165416134862987fc407e88/folder-16541625406298846cd9fa5/damages/folder-165700551362c3e5c9e2284/screenshot20220614at111055am-165700525762c3e4c9c678f.png",
     *                 "isPrivate": "public",
     *                 "mimeType": "image/png",
     *                 "size": 35928.0,
     *                 "folder": "1ecf6cc0-00a5-677e-878d-0242ac130003",
     *                 "type": "locationImages"
     *              },
     *             "tenants": [
     *                  {
     *                      "firstName": "Tenant",
     *                      "lastName": "Lname",
     *                      "email": "tenant@@yopmail.com",
     *                      "address": {
     *                              "street": "Street name",
     *                              "streetNumber": "Street number",
     *                              "city": "city name",
     *                              "country": "India",
     *                              "countryCode": "IN",
     *                              "zipCode": "201212",
     *                              "phone": "0123456789"
     *                      }
     *                  }
     *              ],
     *              "owner": {
     *                  "firstName": "f name",
     *                  "lastName": "l name",
     *                  "email": "admin01@yopmail.com",
     *                  "address": {
     *                      "street": "Bern-Zürichstrasse",
     *                      "streetNumber": "123",
     *                      "city": "Langenthal",
     *                      "country": "Switzerland",
     *                      "countryCode": "CH",
     *                      "zipCode": "123",
     *                      "phone": "+41343434343",
     *                      "landLine": "+41343434343"
     *                  }
     *              },
     *              "admin": {
     *                  "firstName": "Properrty admin",
     *                  "lastName": "Neff",
     *                  "email": "propertyadmin@yopmail.com",
     *                  "address": {
     *                      "street": "Bern-Zürichstrasse",
     *                      "streetNumber": "123",
     *                      "city": "Langenthal",
     *                      "country": "Switzerland",
     *                      "countryCode": "CH",
     *                      "zipCode": "123",
     *                      "phone": "+41343434343",
     *                      "landLine": "+41343434343"
     *                  }
     *              },
     *              "offer": {
     *                  "publicId": "1ed0d7ff-acb8-6718-9bae-0242ac130003"
     *              },
     *              "defect": [
     *                  {
     *                      "defectNumber": "#1",
     *                      "publicId": "1ed13ba1-d2fc-6acc-a552-0242ac130003",
     *                      "title": "new defect",
     *                      "description": "desc",
     *                      "attachent": {
     *                          "publicId": "1ed13bc6-e6dd-66f2-a5d1-0242ac130003",
     *                          "originalName": "any-165959355462eb63526a40e.png",
     *                          "path": "http://localhost:8001/files/property/folder-165416134862987fc407e88/folder-16541625406298846cd9fa5/damages/folder-165958877762eb50a93d3ed/any-165959355462eb63526a40e.png",
     *                          "displayName": "any-165959355462eb63526a40e.png",
     *                          "filePath": "/var/www/app/balu/files/property/folder-165416134862987fc407e88/folder-16541625406298846cd9fa5/damages/folder-165958877762eb50a93d3ed/any-165959355462eb63526a40e.png",
     *                          "isPrivate": "public",
     *                          "mimeType": "image/png",
     *                          "size": 53912.0,
     *                          "folder": "1ecf6cc0-00a5-677e-878d-0242ac130003",
     *                          "type": "defect"
     *                      }
     *                  }
     *              ],
     *               "rating": {
     *                   "publicId": "1ed13c6d-37b6-66ae-8073-0242ac130003",
     *                   "createdOn": "2022-08-04T07:26:59+00:00",
     *                    "ratedBy": {
     *                        "firstName": "f name",
     *                        "lastName": "l name",
     *                        "email": "admin01@yopmail.com"
     *                    },
     *                    "company": {
     *                        "firstName": "Fn",
     *                        "lastName": "Ln",
     *                        "email": "test.company@yopmail.com"
     *                    },
     *                     "rating": 5
     *             }
     *          },
     *          "error": false,
     *          "message": "success"
     *      }
     * @Operation(
     *      tags={"Ticket"},
     *      summary="API end point get details of a ticket which can be accessible by public",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="ticketId", type="string", default="", example="1ecdb203-7397-6490-b46f-0242ac1b0004"),
     *          )
     *       )
     *     ),
     *  @OA\Response(
     *     response=200,
     *     description="Returns ticket details",
     *  ),
     *  @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     *  ),
     *  @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     *  ),
     *  @OA\Tag(name="Ticket")
     * )
     * @param string $ticketId
     * @param Request $request
     * @param LoggerInterface $requestLogger
     * @param GeneralUtility $generalUtility
     * @param DamageService $damageService
     * @param UserService $userService
     * @param string|null $user
     * @return View
     * @Route("/info/{ticketId}/{user}", defaults={"user"=null}, name="balu_ticket_public_details", methods={"GET"})
     */
    public function info(string $ticketId, Request $request, LoggerInterface $requestLogger, GeneralUtility $generalUtility,
                         DamageService $damageService, UserService $userService, ?string $user = null): View
    {
        $curDate = new \DateTime('now');
        try {
            $userObj = $this->getUser();
            if (!is_null($user) && $userService->checkIfIntendedUser($user) instanceof UserIdentity) {
                $userObj = $this->doctrine->getRepository(UserIdentity::class)->findOneBy(['identifier' => $user]);
            }
            $data = $generalUtility->handleSuccessResponse('success',
                $damageService->generateDamageDetails($damageService->validateAndGetDamageObject($ticketId), $request, $userObj, false, Constants::COMPANY_ROLE)
            );
        } catch (ResourceNotFoundException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to loop janitor to the ticket
     *
     *
     * # Request
     *
     *
     * ## Success response ##
     *
     *      {
     *          "currentRole": "object_owner",
     *          "data": {
     *          },
     *          "error": false,
     *          "message": "Janitor added to loop"
     *      }
     * @Operation(
     *      tags={"Ticket"},
     *      summary="API end point get details of a ticket",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               @OA\Property(property="isJanitorEnabled", type="boolean", default="", example=true),
     *          )
     *       )
     *     ),
     *  @OA\Response(
     *     response=200,
     *     description="Returns ticket details",
     *  ),
     *  @OA\Response(
     *     response="400",
     *     description="Returned when request is not valid"
     *  ),
     *  @OA\Response(
     *     response="500",
     *      description="Internal Server Error"
     *  ),
     *  @OA\Tag(name="Ticket")
     * )
     * @param string $ticketId
     * @param GeneralUtility $generalUtility
     * @param Request $request
     * @param LoggerInterface $requestLogger
     * @return View
     * @Route("/loop-janitor/{ticketId}", name="balu_loop_janitor_in_damage", methods={"PUT"})
     */
    public function loopJanitor(string $ticketId, GeneralUtility $generalUtility,
                                Request $request, LoggerInterface $requestLogger): View
    {
        $em = $this->doctrine->getManager();
        $damage = $em->getRepository(Damage::class)->findOneBy(['publicId' => $ticketId]);
        try {
            if (!$damage instanceof Damage) {
                throw new EntityNotFoundException('invalidDamage');
            }
            $status = $request->request->get('isJanitorEnabled');
            $damage->setIsJanitorEnabled($status);
            $em->flush();
            $msg = $status ? 'janitorEnabled' : 'janitorDisabled';
            $data = $generalUtility->handleSuccessResponse($msg);
        } catch (ResourceNotFoundException | \Exception $e) {
            $curDate = new \DateTime();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }
}
