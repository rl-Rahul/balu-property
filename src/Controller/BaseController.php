<?php

/**
 * This file is part of the Balu 2.0 Project package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace App\Controller;

define("CONTAINER_NOT_FOUND", "Container not found!!");

use App\Utils\Constants;
use App\Entity\UserIdentity;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use Symfony\Component\Process\Exception\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * BaseController
 *
 * Controller to manage secured user actions.
 *
 * @package         PITS
 * @subpackage      App
 * @author          Rahul<rahul.rl@pitsolutions.com>
 */
abstract class BaseController extends AbstractFOSRestController
{
    /**
     * @var RequestStack $request
     */
    protected RequestStack $request;

    /**
     * @var string|null $locale
     */
    protected ?string $locale;

    /**
     * @var string|null $currentRole
     */
    protected ?string $currentRole;

    /**
     * @var TranslatorInterface $locale
     */
    protected TranslatorInterface $translator;

    /**
     * @var $tokenStorage
     */
    protected $serverRequest;

    /**
     * @var $event
     */
    protected $event;

    /**
     * @var ManagerRegistry $doctrine
     */
    protected ManagerRegistry $doctrine;

    /**
     * @var ParameterBagInterface $parameterBag
     */
    protected ParameterBagInterface $parameterBag;

    /**
     * @var SecurityService $securityService
     */
    protected SecurityService $securityService;

    /**
     * Constructor
     *
     * @param RequestStack $request
     * @param TranslatorInterface $translator
     * @param ManagerRegistry $doctrine
     * @param ParameterBagInterface $parameterBag
     * @param SecurityService $securityService
     */
    public function __construct(RequestStack $request, TranslatorInterface $translator, ManagerRegistry $doctrine,
                                ParameterBagInterface $parameterBag, SecurityService $securityService)
    {
        $this->request = $request;
        $this->locale = $this->request->getCurrentRequest()->headers->has('locale') ?
        $this->request->getCurrentRequest()->headers->get('locale') : $parameterBag->get('default_language');
        $this->currentRole = $this->request->getCurrentRequest()->headers->has('currentRole') ?
            $this->request->getCurrentRequest()->headers->get('currentRole') : null;
        $this->translator = $translator;
        $this->doctrine = $doctrine;
        $this->parameterBag = $parameterBag;
        $this->securityService = $securityService;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services['translator'] = TranslatorInterface::class;
        return $services;
    }

    /**
     * Function to handle response
     *
     * @param $data
     * @param array $messageParams
     *
     * @return View
     */
    protected function response($data, array $messageParams = []): View
    {
        $response['currentRole'] = $this->currentRole;
        $status = 400;
        if (\is_array($data) && isset($data['data'])) {
            $response['data'] = $data['data'];
        } else if (\is_object($data)) {
            $response['data'] = $data;
        } else {
            $response['data'] = [];
        }
        $response['error'] = $data['error'];
        if (array_key_exists('status', $data) && $data['status'] != '') {
            $status = $data['status'];
            unset($data['status']);
        }
        if (array_key_exists('message', $data) && isset($data['message']) && !\is_null($data['message'])) {
            $response['message'] = $this->translator->trans($data['message'], $messageParams, null, $this->locale);
        }
        $view = View::create();
        return $view->setData($response)->setStatusCode($status);
    }

    /**
     * Function to create named form
     *
     * @param $type
     * @param $data
     * @param $options
     *
     * @return object
     * @throws \Exception
     */
    protected function createNamedForm($type, $data = null, array $options = []): object
    {
        try {
            return $this->container->get('form.factory')->createNamed('', $type, $data, $options);
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            throw new \Exception(CONTAINER_NOT_FOUND);
        }
    }

    /**
     * @return object|UserIdentity|null
     */
    protected function getUser(): ?UserIdentity
    {
        $identity = $this->doctrine->getManager();
        return $identity->getRepository(UserIdentity::class)->findOneBy(['user' => parent::getUser()]);
    }

    /**
     * @param FormInterface $form
     * @return array
     */
    protected function getErrorsFromForm(FormInterface $form): array
    {
        $errors = array();
        foreach ($form->getErrors(true, false) as $error) {
            if (method_exists($error, 'getForm')) {
                $cause = $error->getForm()->getName();
                $msg = $error->current()->getMessage();
            } else {
                $params = $error->getCause()->getParameters();
                $cause = trim(array_values($params)[0], '"');
                $msg = $error->getMessage();
            }
//            $errors[$cause] = $msg;
            $errors[] = $msg;
        }

//        foreach ($form->all() as $childForm) {
//            if ($childForm instanceof FormInterface) {
//                if ($childErrors = $this->getErrorsFromForm($childForm)) {
//                    $errors[$childForm->getName()] = $childErrors;
//                }
//            }
//        }
        return $errors;
    }

    /**
     * Validating administrator
     *
     * @param Request $request
     * @param string $ownerIdKey
     * @return bool
     */
    protected function checkPropertyAdminAccess(Request $request, string $ownerIdKey): bool
    {
        $loggedInUser = $this->getUser();
        $role = $this->securityService->fetchUserRole($loggedInUser);
        if ($role == Constants::DIRECTORY_TYPES[2]) {
            if (!is_numeric($request->get($ownerIdKey))) {
                throw new InvalidArgumentException('invalidArgument');
            }
            $ownerId = $request->get($ownerIdKey);
            if (!$this->securityService->loggedInUserValidAdminOf($ownerId)) {
                throw new UnsupportedUserException('notAdmin');
            }
        }
        return true;
    }
}