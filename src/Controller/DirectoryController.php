<?php

/**
 * This file is part of the Balu Property package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Directory;
use App\Entity\Document;
use App\Entity\PropertyUser;
use App\Exception\FormErrorException;
use App\Form\DirectoryType;
use App\Service\DMSService;
use App\Utils\GeneralUtility;
use Doctrine\ORM\EntityNotFoundException;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\View\View;
use OpenApi\Annotations as OA;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Security;
use App\Service\DirectoryService;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\UserIdentity;
use App\Service\UserService;
use App\Entity\User;
use App\Utils\ContainerUtility;
use App\Utils\Constants;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Psr\Log\LoggerInterface;
use const http\Client\Curl\PROXY_HTTP;

/**
 * DirectoryController
 *
 * Controller to manage directory related actions.
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 * @Route("/directory")
 */
final class DirectoryController extends BaseController
{
    /**
     * API end point to list current user invitees
     * ### api/2.0/directory/individual
     * ### api/2.0/directory/company
     * ### api/2.0/directory/admin
     * ### api/2.0/directory/users
     * ### api/2.0/directory/people
     *
     * ## Success response ##
     *
     *      {
     *           "data": [
     *               {
     *                   "identifier": 1,
     *                   "publicId": {
     *                       "uid": "1eca4592-ff84-6632-ba77-0242ac120004"
     *                   },
     *                   "userId": 13,
     *                   "invitorPublicId": {
     *                       "uid": "1eca4592-dfb9-6ea6-a8b5-0242ac120004"
     *                   },
     *                   "firstName": "CH",
     *                   "lastName": "Langenthal",
     *                   "email": "test11@yopmail.com",
     *                   "isSystemGeneratedEmail": false
     *                 },
     *               }
     *           ],
     *      "error": false,
     *      "message": "Data fetched"
     *  }
     * @Operation(
     *      tags={"Directory"},
     *      summary="API end point to list directories of individuals, companies and property administrators.",
     *      @Security(name="Bearer"),
     *      @OA\Parameter(
     *       name="type",
     *       in="query",
     *       description="Directory Type for list(individual, company, property_admin)",
     *       required=true,
     *       @OA\Schema(
     *         type="string",
     *       ),
     *     ),
     * )
     * @OA\Response(
     *     response=200,
     *     description="Returns directory list",
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
     * @OA\Tag(name="Directory")
     * @param string $type
     * @param GeneralUtility $generalUtility
     * @param DirectoryService $directoryService
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @param ParameterBagInterface $params
     * @param DMSService $DMSService
     * @param string|null $property
     * @return View
     * @Route("/{type}/{property}", defaults={"property" = null}, name="balu_list_directory", methods={"GET"})
     */
    public function index(string $type, GeneralUtility $generalUtility, DirectoryService $directoryService,
                          LoggerInterface $requestLogger, Request $request, ParameterBagInterface $params, DMSService $DMSService,
                          ?string $property = null): View
    {
        $iconDir = $params->get('icon_folder');
        $curDate = new \DateTime('now');
        try {
            $em = $this->doctrine->getManager();
            switch ($type) {
                case Constants::DIRECTORY_TYPES[0]:
                    $result = $directoryService->getDirectories($this->getUser(), null, $this->locale);
                    break;
                case Constants::DIRECTORY_TYPES[1]:
                    $list = $em->getRepository(UserIdentity::class)->getCompanies($this->getUser());
                    $result = array_map(function ($users) use ($iconDir, $generalUtility, $request, $DMSService, $em) {
                        $categoryArray = [];
                        $baseUrl = $generalUtility->getBaseUrl($request);
                        if (is_null($users['name'])) {
                            $users['name'] = $users['firstName'] . ' ' . $users['lastName'];
                        }
                        $users['isRegisteredUser'] = (bool)$users['isRegisteredUser'];
                        $categoriesList = $em->getRepository(UserIdentity::class)
                            ->getCompanyCategories(['active' => true, 'deleted' => false, 'company' => $users['user']],
                                $this->getUser()->getLanguage());
                        foreach ($categoriesList as $eachCategory) {
                            $eachCategory['icon'] = $baseUrl . "/$iconDir" . $eachCategory['icon'];
                            $categoryArray[] = $eachCategory;
                            unset($eachCategory['catName'], $eachCategory['publicId'], $eachCategory['icon'], $eachCategory['name']);
                        }
                        $users['categories'] = $categoryArray;
                        $document = $em->getRepository(Document::class)->findOneBy(['user' => $users['user'], 'property' => null, 'apartment' => null, 'type' => 'coverImage', 'isActive' => true]);
                        if ($document instanceof Document) {
                            $users['document'] = $DMSService->getThumbnails($document->getOriginalName(), $request->getSchemeAndHttpHost() . '/' . $document->getPath());
                        }
                        return $users;
                    }, $list);
                    break;
                case Constants::DIRECTORY_TYPES[2]:
                    $list = $em->getRepository(UserIdentity::class)->getAdministrators($this->getUser());
                    $result = array_map(function ($users) {
                        $users['isRegisteredUser'] = (bool)$users['isRegisteredUser'];
                        if (is_null($users['name'])) {
                            $users['name'] = $users['firstName'] . ' ' . $users['lastName'];
                        }
                        return $users;
                    }, $list);
                    break;
                case Constants::DIRECTORY_TYPES[3]:
                    $list = $em->getRepository(UserIdentity::class)->getJanitors($this->getUser());
                    $result = array_map(function ($users) {
                        $users['isRegisteredUser'] = (bool)$users['isRegisteredUser'];
                        return $users;
                    }, $list);
                    break;
                case Constants::DIRECTORY_TYPES[4]:
                    $result['individual'] = $directoryService->getDirectories($this->getUser());
                    $result['companies'] = $em->getRepository(UserIdentity::class)->getCompanies($this->getUser());
                    break;
                case Constants::DIRECTORY_TYPES[5]:
                    $result['people'] = $directoryService->getPeopleDirectory($property);
                    break;
                default:
                    throw new ResourceNotFoundException("invalidType");
            }
            $data = $generalUtility->handleSuccessResponse('listFetchSuccess', $result);
        } catch (ResourceNotFoundException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }
        return $this->response($data);
    }

    /**
     * API end point to add a individual to directory.
     *
     * # Request
     * In request body, system expects details as JSON.
     * ## Example request to add individual
     *       {
     *           "email": "test16@yopmail.com",
     *           "firstName": "CH",
     *           "lastName": "Langenthal",
     *           "phone": "12.2221",
     *           "landLine": "Keller",
     *           "role": "property_admin",
     *           "sendInvite": 1
     *       }
     * # Response
     * ## Success response ##
     *       {
     *           "data": {
     *               "firstName": "CH",
     *               "lastName": "Langenthal",
     *               "email": "test19@yopmail.com"
     *           },
     *           "error": false,
     *           "message": "Invitation successful"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "propertyCreateFail"
     *       }
     * @Route("/add", name="balu_add_individual", methods={"POST"})
     * @Operation(
     *      tags={"Directory"},
     *      summary="API end point to add an individual",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="email", type="string", default="", example=""),
     *               @OA\Property(property="firstName", type="string", default="", example=""),
     *               @OA\Property(property="lastName", type="string", default="", example=""),
     *               @OA\Property(property="phone", type="string", default="", example=""),
     *               @OA\Property(property="landLine", type="string", default="", example=""),
     *               @OA\Property(property="role", type="string", default="", example=""),
     *               @OA\Property(property="sendInvite", type="bool", default="", example="true"),
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
     * @param GeneralUtility $generalUtility
     * @param DirectoryService $directoryService
     * @param UserPasswordHasherInterface $passwordHasher
     * @param UserService $userService
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function add(Request $request, GeneralUtility $generalUtility, DirectoryService $directoryService,
                        UserPasswordHasherInterface $passwordHasher, UserService $userService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['property' => $request->request->get('email'), 'deleted' => false]);
        $em->beginTransaction();
        try {
            if ($user instanceof User) {
                $directoryService->checkUserExistsInDirectory($user->getUserIdentity(), $request->request->get('property'));
                $data = $directoryService->addExistingUserToDirectory($user, $request, $this->getUser());
            } else {
                $userIdentity = new UserIdentity();
                $form = $this->createNamedForm(DirectoryType::class, new Directory());
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $userIdentity->setLanguage($this->locale);
                    $directory = $directoryService->saveIndividual($form, $userIdentity, $passwordHasher, $this->getUser(), $this->locale, $request);
                    $em->flush();
                    $em->refresh($userIdentity);
                    $data = $generalUtility->handleSuccessResponse('userRegisteredSuccessfulWithoutMail',
                        $userService->getUserData($userIdentity, $this->getUser(), $directory->getProperty()));
                    if ($request->request->get('sendInvite')) {
                        if ($directory instanceof Directory) {
                            $directory->setInvitedAt(new \DateTime("now"));
                        }
                        $isSystemGeneratedEmail = $form->has('isSystemGeneratedEmail') && true === $form->get('isSystemGeneratedEmail')->getData();
                        $message = $isSystemGeneratedEmail ? 'userRegisteredSuccessfulWithoutMail' : 'userRegisteredSuccessful';
                        $data = $generalUtility->handleSuccessResponse($message,
                            $userService->getUserData($userIdentity, $this->getUser(), $directory->getProperty()));
                    }
                } else {
                    throw new FormErrorException('formError');
                }
            }
            $em->flush();
            $em->commit();
        } catch (CustomUserMessageAccountStatusException | EntityNotFoundException | InvalidPasswordException | \Exception $e) {
            $em->rollback();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }
        return $this->response($data);
    }

    /**
     * API end point to search directories
     * # Request
     * In request body, system expects following data.
     * ### Route /api/2.0/directory/search/individual, /api/2.0/directory/search/company, /api/2.0/directory/search/property_admin, /api/2.0/directory/search/janitor
     * ## Example request to update user settings
     *      {
     *          "search": "parameter",
     *      }
     * ## Success response ##
     *      {
     *           "data": [
     *               {
     *                   "identifier": 1,
     *                   "publicId": {
     *                       "uid": "1eca4592-ff84-6632-ba77-0242ac120004"
     *                   },
     *                   "userId": 13,
     *                   "invitorPublicId": {
     *                       "uid": "1eca4592-dfb9-6ea6-a8b5-0242ac120004"
     *                   },
     *                   "firstName": "CH",
     *                   "lastName": "Langenthal",
     *                   "email": "test11@yopmail.com"
     *                 },
     *               }
     *           ],
     *      "error": false,
     *      "message": "Data fetched"
     *  }
     * @Operation(
     *      tags={"Directory"},
     *      summary="API end point to seacrh directories of indivituals, companies and property administrators.",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="application/json",
     *           @OA\Schema(
     *               @OA\Property(property="search", type="string", default="", example="hellenkeller@example.com"),
     *           )
     *        )
     *      )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Returns directory list",
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
     * @OA\Tag(name="Directory")
     * @param Request $request
     * @param string $type
     * @param GeneralUtility $generalUtility
     * @param DirectoryService $directoryService
     * @param LoggerInterface $requestLogger
     * @param ParameterBagInterface $params
     * @param string|null $property
     * @return View
     * @Route("/search/{type}/{property}", defaults={"property" = null}, name="balu_search_directory", methods={"POST"})
     */
    public function search(Request $request, string $type, GeneralUtility $generalUtility, DirectoryService $directoryService,
                           LoggerInterface $requestLogger, ParameterBagInterface $params, ?string $property = null): View
    {
        $curDate = new \DateTime('now');
        try {
            $em = $this->doctrine->getManager();
            $parameter = $request->request->get('search');
            switch ($type) {
                case Constants::DIRECTORY_TYPES[0]:
                    $result = $directoryService->searchIndividual($this->getUser(), $parameter, $this->currentRole, $this->locale);
                    break;
                case Constants::DIRECTORY_TYPES[1]:
                    $iconDir = $generalUtility->getBaseUrl($request) . '/' . $params->get('icon_folder');
                    $result = $em->getRepository(UserIdentity::class)->searchCompanies($parameter, $iconDir, $this->getUser());
                    break;
                case Constants::DIRECTORY_TYPES[2]:
                    $result = $em->getRepository(UserIdentity::class)->searchAdministrators($parameter, $this->getUser());
                    break;
                case Constants::DIRECTORY_TYPES[3]:
                    $result = $em->getRepository(UserIdentity::class)->searchJanitors($this->getUser(), $parameter);
                    break;
                case Constants::DIRECTORY_TYPES[4]:
                    $iconDir = $generalUtility->getBaseUrl($request) . '/' . $params->get('icon_folder');
                    $result['companies'] = $em->getRepository(UserIdentity::class)->searchCompanies($parameter, $iconDir, $this->getUser());
                    $result['individual'] = $directoryService->searchIndividual($this->getUser(), $parameter, $this->currentRole, $this->locale);
                    break;
                case Constants::DIRECTORY_TYPES[5]:
                    $result['people'] = $directoryService->searchPeople($property, $parameter);
                    break;
                default:
                    throw new ResourceNotFoundException("invalidType");
            }
            $data = $generalUtility->handleSuccessResponse('listFetchSuccess', $result);
        } catch (ResourceNotFoundException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }
        return $this->response($data);
    }

    /**
     * API end point to add a individual to directory.
     *
     * ### api/2.0/directory/edit
     * # Request
     * In request body, system expects details as JSON.
     * ## Example request to add individual
     *       {
     *           "firstName": "CH",
     *           "lastName": "Langenthal",
     *           "phone": "2121112442",
     *           "landLine": "22112255222",
     *           "publicId": "1eca4592-dfb9-6ea6-a8b5-0242ac120004"
     *       }
     * # Response
     * ## Success response ##
     *       {
     *           "data": {
     *               "firstName": "CH",
     *               "lastName": "Langenthal",
     *               "email": "test19@yopmail.com"
     *           },
     *           "error": false,
     *           "message": "Invitation successful"
     *       }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "propertyCreateFail"
     *       }
     * @Route("/edit", name="balu_edit_individual", methods={"POST"})
     * @Operation(
     *      tags={"Directory"},
     *      summary="API end point to add an individual",
     *      @Security(name="Bearer"),
     *      @OA\RequestBody(
     *       required=false,
     *       @OA\MediaType(
     *           mediaType="multipart/json",
     *           @OA\Schema(
     *               @OA\Property(property="firstName", type="string", default="", example=""),
     *               @OA\Property(property="lastName", type="string", default="", example=""),
     *               @OA\Property(property="phone", type="string", default="", example=""),
     *               @OA\Property(property="landLine", type="string", default="", example=""),
     *               @OA\Property(property="publicId", type="string", default="", example=""),
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
     * @param GeneralUtility $generalUtility
     * @param DirectoryService $directoryService
     * @param UserService $userService
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function edit(Request $request, GeneralUtility $generalUtility, DirectoryService $directoryService,
                         UserService $userService, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $directory = $em->getRepository(Directory::class)->findOneBy(['publicId' => $request->request->get('publicId')]);
        $em->beginTransaction();
        try {
            if (!$directory instanceof Directory) {
                throw new UserNotFoundException('userNotFound');
            }
            if ($directory->getUser()->getUser()->getProperty() !== $request->request->get('email')) {
                $userIdentity = $em->getRepository(UserIdentity::class)->findOneByEmail($request->request->get('email'));
                $directoryService->checkUserExistsInDirectory($userIdentity, $request->request->get('property'));
            }
            $form = $this->createNamedForm(DirectoryType::class, $directory);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $directoryService->updateIndividualDetails($form, $directory);
                if ($request->get('sendInvite') == true) {
                    $user = $directory->getUser();
                    if (is_null($user->getUser()->getFirstLogin())) {
                        $directoryService->resendInvitation($user, $this->locale);
                        $user->setInvitedAt(new \DateTime("now"));
                        $em->flush();
                    }
                }
            } else {
                throw new UserNotFoundException('userEditFailed');
            }

            $em->refresh($directory->getUser());
            $em->commit();
            $data = $generalUtility->handleSuccessResponse('userEditedSuccessful',
                $userService->getUserData($directory->getUser()));
        } catch (InvalidPasswordException | UserNotFoundException | \Exception $e) {
            $em->rollback();
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to add a individual to directory.
     *
     * # Request
     * In url, system expects individual uuid.
     * # Response
     * ## Success response ##
     *       {
     *       "data": {
     *           "details": {
     *               "firstName": "Test",
     *               "lastName": "User",
     *               "email": "testbaluuser@yopmail.com",
     *               "isRegisteredUser": false,
     *               "invitedOn": "2022-05-04T13:07:31+00:00"
     *           },
     *           "allocations": {
     *              "object": [
     *                  {
     *                   "roleName": "Tenant",
     *                   "roleKey": "tenant",
     *                   "objectName": "test",
     *                   "propertyName": "kk"
     *                  }
     *              ],
     *              "property": []
     *           }
     *       },
     *       "error": false,
     *       "message": "dataFetchSuccess"
     *   }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "formError"
     *       }
     * @Route("/{uuid}/{type}/detail/{property}", defaults={"property" = null}, name="balu_get_individual", methods={"GET"})
     * @Operation(
     *      tags={"Directory"},
     *      summary="API end point to get detail of an individual",
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
     * @param string $uuid
     * @param string $type
     * @param GeneralUtility $generalUtility
     * @param DirectoryService $directoryService
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @param string|null $property
     * @return View
     */
    public function detail(string $uuid, string $type, GeneralUtility $generalUtility, DirectoryService $directoryService,
                           LoggerInterface $requestLogger, Request $request, ?string $property = null): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        try {
            $entity = in_array($type, [Constants::DIRECTORY_TYPES[0], Constants::DIRECTORY_TYPES[5]]) ? 'App\\Entity\\Directory' : 'App\\Entity\\UserIdentity';
            $user = $em->getRepository($entity)->findOneBy(['publicId' => $uuid]);
            if (!$user instanceof $entity) {
                throw new \Exception('invalidUser', 400);
            }
            $details = $directoryService->getUserDetail($request, $user, $this->getUser(), $this->locale, $this->currentRole, $property);
            $data = $generalUtility->handleSuccessResponse('dataFetchSuccess', $details);
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to resend invitation.
     *
     * # Request
     * In url, system expects individual uuid.
     * In request body, system expects details as JSON.
     * ## Example request to add individual
     *       {
     *           "role": "individual"
     *       }
     * # Response
     * ## Success response ##
     *       {
     *       "data": {
     *       },
     *       "error": false,
     *       "message": "resendSuccess"
     *   }
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "formError"
     *       }
     * @Route("/resend/{uuid}", name="balu_resend_invitation", methods={"POST"})
     * @Operation(
     *      tags={"Directory"},
     *      summary="API end point to resend invitation",
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
     * @param string $uuid
     * @param GeneralUtility $generalUtility
     * @param DirectoryService $directoryService
     * @param Request $request
     * @param LoggerInterface $requestLogger
     * @return View
     */
    public function resendInvitation(string $uuid, GeneralUtility $generalUtility, DirectoryService $directoryService,
                                     Request $request, LoggerInterface $requestLogger): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        try {
            if ($request->get('role') === Constants::DIRECTORY_TYPES[0]) {
                $user = $em->getRepository(Directory::class)->findOneBy(['publicId' => $uuid, 'deleted' => false]);
                if (!$user instanceof Directory) {
                    throw new \Exception('invalidUser', 400);
                }
                $user->setInvitedAt(new \DateTime("now"));
                $user = $user->getUser();
            } else {
                $user = $em->getRepository(UserIdentity::class)->findOneBy(['publicId' => $uuid, 'deleted' => false]);
                if (!$user instanceof UserIdentity) {
                    throw new \Exception('invalidUser', 400);
                }
                $user->setInvitedAt(new \DateTime("now"));
            }
            $directoryService->resendInvitation($user, $this->locale);
            $data = $generalUtility->handleSuccessResponse('resendInvitationSuccess');
            $em->flush();
        } catch (\Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }

    /**
     * API end point to add a individual to directory.
     *
     * # Request
     * In url, system expects individual uuid.
     * # Response
     * ## Success response ##
     * ## Failed response ##
     * ### due to validation error
     *      {
     *          "data": "No data provided",
     *          "error": true,
     *          "message": "formError"
     *       }
     * @Route("/delete/{uuid}", name="balu_delete_individual", methods={"DELETE"})
     * @Operation(
     *      tags={"Directory"},
     *      summary="API end point to get detail of an individual",
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
     * @param string $uuid
     * @param GeneralUtility $generalUtility
     * @param DirectoryService $directoryService
     * @param LoggerInterface $requestLogger
     * @param Request $request
     * @return View
     */
    public function delete(string $uuid, GeneralUtility $generalUtility, DirectoryService $directoryService,
                           LoggerInterface $requestLogger, Request $request): View
    {
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $directory = $em->getRepository(Directory::class)->findOneBy(['publicId' => $uuid]);
        try {
            if (!$directory instanceof Directory) {
                throw new \Exception('invalidUser', 400);
            }
            $directoryService->checkAllocationStatus($directory);
            $directory->setDeleted(true);
            $userAllocations = $em->getRepository(PropertyUser::class)->findBy([
                'user' => $directory->getUser(),
                'property' => $directory->getProperty(),
                'deleted' => false
            ]);
            if (!empty($userAllocations)) {
                foreach ($userAllocations as $allocation) {
                    if ($allocation instanceof PropertyUser) {
                        $allocation->setDeleted(true);
                    }
                }
            }
            $em->flush();
            $data = $generalUtility->handleSuccessResponse('deletedSuccessfully');
        } catch (CustomUserMessageAccountStatusException | InvalidPasswordException | \Exception $e) {
            $requestLogger->error($curDate->format("Y-m-d H:i:s") . ' ' . $request->attributes->get('_route') . ' ' . $e->getMessage());
            $data = $generalUtility->handleFailedResponse($e->getMessage());
        }

        return $this->response($data);
    }
}
