<?php

/**
 * This file is part of the Wedoit Project package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventListener;

use App\Entity\User;
use App\Utils\Constants;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use App\Entity\RequestLogger;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Uuid as UuidConstraint;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Entity\Property;
use App\Entity\Apartment;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\UserIdentity;
use App\Entity\Damage;
use App\Service\DamageService;
use App\Entity\Message;
use App\Service\ObjectService;
use App\Service\UserService;

/**
 * RequestListener
 *
 * Listener to handle request objects
 *
 * @package         Wedoit
 * @subpackage      App
 * @author          Rahul <rahul.rl@pitsolutions.com>
 */
final class RequestListener
{
    /**
     * @var ManagerRegistry $em
     */
    private static ManagerRegistry $em;

    /**
     * @var DamageService $damageService ;
     */
    private DamageService $damageService;

    /**
     * @var ObjectService $objectService ;
     */
    private ObjectService $objectService;

    /**
     * @var UserService $userService ;
     */
    private UserService $userService;

    /**
     *
     * @param ManagerRegistry $entityManager
     * @param TokenStorageInterface $tokenStorage
     * @param DamageService $damageService
     * @param ObjectService $objectService
     * @param UserService $userService
     */
    public function __construct(ManagerRegistry $entityManager, TokenStorageInterface $tokenStorage, DamageService $damageService, ObjectService $objectService, UserService $userService)
    {
        self::$em = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->damageService = $damageService;
        $this->objectService = $objectService;
        $this->userService = $userService;
    }

    /**
     * @param RequestEvent $event
     *
     * @return void
     * @throws \Exception
     */
    public function onKernelRequest(RequestEvent $event)
    {
        $route = self::getRouteValue($event);
        if (str_starts_with($route, 'balu_ticket')) {
            $this->checkTicketAccess($event);
        } elseif (str_starts_with($route, 'balu_message')) {
            //$this->checkMessageAccess($event);
        } else {
            $this->checkAccess($event);
        }
        if ($route === 'app.swagger_ui' || self::exceptionHandler($event) || self::emptyRequest($event)) {
            return;
        }
        switch ($route) {
            case 'balu_login':
                $request = self::unParsedData($event); //Replace this case with your login route name
                break;
            default:
                $request = self::parsedData($event);
        }
        $event->getRequest()->request->replace($request);
    }

    /**
     * @param ControllerEvent $event
     *
     * @return void
     */
    public function onKernelController(ControllerEvent $event)
    {
        //        Mandatory field validations
        $requests = $event->getRequest()->request->all();
        $responses = array();
        if (is_array($requests) && isset($requests['validation'])) {
            foreach ($requests['validation'] as $property) {
                $responses[] = ucfirst($property . ' is mandatory');
            }
            $event->setController(
                function () use ($responses) {
                    return new JsonResponse($responses, 400);
                }
            );
        }

        if (!is_array($requests)) {
            $requests = (array)$requests;
        }
        $this->logRequest($event->getRequest(), $requests);
    }

    /**
     * @param RequestEvent $event
     *
     * @return string
     */
    protected static function getRouteValue(RequestEvent $event): ?string
    {
        return $event->getRequest()->attributes->get("_route");
    }

    /**
     * @param RequestEvent $event
     *
     * @return bool
     */
    protected static function exceptionHandler(RequestEvent $event): bool
    {
        return $event->getRequest()->attributes->has("exception");
    }

    /**
     * @param RequestEvent $event
     *
     * @return bool
     */
    protected static function emptyRequest(RequestEvent $event): bool
    {
        $content = $event->getRequest()->getContent();
        if (\is_null($content) || $content == '') {
            return true;
        }
        return false;
    }

    /**
     * @param RequestEvent $event
     *
     * @return array
     */
    protected static function parsedData(RequestEvent $event): ?array
    {
        $parameters = array();
        $validations = array();
        $content = $event->getRequest()->getContent();
        foreach (self::jsonToArray($content) as $property => $value) {
            if (strpos($property, 'm_') !== false && ($value === '' || $value == null)) {
                $validations['validation'][] = str_replace("m_", "", $property);
            } else {
                $parameters[lcfirst(str_replace('_', '',
                    ucwords(str_replace('m_', '', $property), '_')))] = $value;
            }
        }
        return !empty($validations) ? $validations : $parameters;
    }

    /**
     * @param RequestEvent $event
     *
     * @return array
     */
    protected static function unParsedData(RequestEvent $event): array
    {
        return self::jsonToArray($event->getRequest()->getContent());
    }

    /**
     * @param string $json
     *
     * @return array
     */
    protected static function jsonToArray(string $json): array
    {
        return json_decode($json, true);
    }

    /**
     *
     * @param array $requests
     * @param Request $requestBag
     * @return void
     */
    public function logRequest(Request $requestBag, array $requests = []): void
    {
        if (!empty($requests)) {
            if (isset($requests['password'])) {
                $requests['password'] = str_repeat('*', strlen($requests['password']));
            }
            $requestLogger = new RequestLogger();
            $uri = $requestBag->getHttpHost() . $requestBag->getRequestUri();
            $requestLogger->setRequestMethod($requestBag->getMethod())
                ->setRequestParam($requests)
                ->setClientIp($requestBag->getClientIp())
                ->setUri($uri);
            self::$em->getManager()->persist($requestLogger);
            self::$em->getManager()->flush();
        }
    }

    /**
     *
     * @param RequestEvent $event
     * @throws \Exception
     */
    private function checkAccess(RequestEvent $event): void
    {
        $params = $event->getRequest()->attributes->get("_route_params");
        $oProperty = null;
        if (!empty($params) && !is_null($this->tokenStorage->getToken())) {
            $user = $this->tokenStorage->getToken()->getUser();
            $currentUser = self::$em->getManager()->getRepository(UserIdentity::class)->findOneBy(['user' => $user]);
            if (array_key_exists('property', $params) && !is_null($params['property'])) {
                $property = $params['property'];
                if ($this->validateUuid($property)) {
                    //if property user should be owner or property admin
                    $oProperty = self::$em->getManager()->getRepository(Property::class)->findOneBy(['publicId' => $params['property']]);
                }
            } else if (array_key_exists('object', $params)) {
                $object = $params['object'];
                if ($this->validateUuid($object)) {
                    //if property user should be owner or property admin
                    $oObject = self::$em->getManager()->getRepository(Apartment::class)->findOneBy(['publicId' => $params['object']]);
                    $oProperty = $oObject->getProperty();
                }
            }

            if ($oProperty instanceof Property) {
                $currentRoles = array_column($this->userService->getUserRoles($currentUser), 'roleKey');
                if (!in_array('admin', $currentRoles)) {
                    $aptArray = [];
                    foreach ($oProperty->getApartments() as $apt) {
                        $aptArray[] = $apt->getPublicId();
                    }
                    $users = $this->objectService->getObjectUsers($aptArray, false);
                    $users += [$oProperty->getUser(), $oProperty->getAdministrator(), $oProperty->getJanitor()];
                    if (!in_array($currentUser, $users)) {
                        throw new AccessDeniedException('accessDenied');
                    }
                }
            }

        }
    }

    /**
     *
     * @param string $uuid
     * @return bool
     * @throws \Exception
     */
    private function validateUuid(string $uuid): bool
    {
        $validator = Validation::createValidator();
        $uuidConstraint = new UuidConstraint();
        $errors = $validator->validate($uuid, $uuidConstraint);
        if (count($errors) > 0) {
            throw new \Exception('invalidObject');
        }

        return true;
    }

    /**
     *
     * @param RequestEvent $event
     * @throws \Exception
     */
    private function checkTicketAccess(RequestEvent $event): void
    {
        $params = $event->getRequest()->attributes->get("_route_params");
        $currentRole = $event->getRequest()->headers->get('currentRole');
        if (!empty($params) && $currentRole !== Constants::GUEST_ROLE) {
            $users = [];
            $user = $this->tokenStorage->getToken()->getUser();
            if ($user instanceof User) {
                $currentUser = self::$em->getManager()->getRepository(UserIdentity::class)->findOneBy(['user' => $user]);
                if (strcmp($currentRole, 'company_user') === 0) {
                    $currentUser = $currentUser->getParent();
                }
                if (array_key_exists('ticketId', $params)) {
                    $ticket = $params['ticketId'];
                    if ($this->validateUuid($ticket)) {
                        $damage = self::$em->getManager()->getRepository(Damage::class)->findOneBy(['publicId' => $ticket]);
                        $users = $this->damageService->getTicketUsers($damage, false);
                    }
                }
                if (!in_array($currentUser, $users)) {
                    throw new AccessDeniedException('accessDenied');
                }
            }
        }
    }

    /**
     *
     * @param RequestEvent $event
     * @throws \Exception
     */
    private function checkMessageAccess(RequestEvent $event): void
    {
        $params = $event->getRequest()->attributes->get("_route_params");
        if (!empty($params)) {
            $users = $aptArray = [];
            $user = $this->tokenStorage->getToken()->getUser();
            $currentUser = self::$em->getManager()->getRepository(UserIdentity::class)->findOneBy(['user' => $user]);
            if (array_key_exists('messageId', $params)) {
                $messageId = $params['messageId'];
                if ($this->validateUuid($messageId)) {
                    $message = self::$em->getManager()->getRepository(Message::class)->findOneBy(['publicId' => $messageId]);
                    if ($message->getType()->getTypeKey() === 'ticket') {
                        $users = $this->damageService->getTicketUsers($message->getDamage(), false);
                    } else {
                        foreach ($message->getApartments() as $apt) {
                            $aptArray[] = $apt->getPublicId();
                        }
                        $users = $this->objectService->getObjectUsers($aptArray, false);
                    }
                }
            }
            if (!in_array($currentUser, $users)) {
                throw new AccessDeniedException('accessDenied');
            }
        }
    }
}