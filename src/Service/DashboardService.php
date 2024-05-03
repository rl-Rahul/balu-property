<?php

namespace App\Service;

use App\Entity\Property;
use App\Entity\Role;
use App\Entity\UserIdentity;
use Doctrine\Persistence\ManagerRegistry;
use App\Service\DamageService;
use App\Utils\ContainerUtility;
use App\Entity\Damage;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Utils\Constants;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DashboardService
 * @package App\Service
 */
class DashboardService extends BaseService
{
    /**
     * @var ManagerRegistry $doctrine
     */
    private ManagerRegistry $doctrine;

    /**
     * @var ContainerUtility $containerUtility ;
     */
    private ContainerUtility $containerUtility;

    /**
     * @var DamageService $damageService
     */
    private DamageService $damageService;
    /**
     * @var ParameterBagInterface $params ;
     */
    private ParameterBagInterface $params;
    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     *
     * @param ManagerRegistry $doctrine
     * @param ContainerUtility $containerUtility
     * @param DamageService $damageService
     * @param ParameterBagInterface $parameterBag
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ManagerRegistry $doctrine,
        ContainerUtility $containerUtility,
        DamageService $damageService,
        ParameterBagInterface $parameterBag,
        TranslatorInterface $translator
    )
    {
        $this->doctrine = $doctrine;
        $this->containerUtility = $containerUtility;
        $this->damageService = $damageService;
        $this->params = $parameterBag;
        $this->translator = $translator;
    }


    /**
     * @param UserIdentity $user
     * @param string $currentUserRole
     * @param Request $request
     * @return array|null
     * @throws \Exception
     */
    public function getDashboard(UserIdentity $user, string $currentUserRole, Request $request): ?array
    {
        $em = $this->doctrine->getManager();
        $data = [];
        $roles = $user->getRole();
        if (count($roles) === 0) {
            return $this->getDefaultDashboard($currentUserRole, $user->getLanguage());
        }
        $data['tickets'] = $this->damageService->getDashboardTickets($request, $user, $currentUserRole);
        $role = $em->getRepository(Role::Class)->findOneBy(['roleKey' => $currentUserRole]);
        $data['property'] = $this->formatResponse('properties', $em->getRepository(Property::Class)->countProperties($user, $role, true), null, null, $user->getLanguage());
        $data['object'] = $this->formatResponse('objects', $em->getRepository(Property::Class)->countObjects($user, $role, true), null, null, $user->getLanguage());
        $data['tenant'] = $this->formatResponse('activeTenants', $em->getRepository(Property::Class)->countTenants($user, $role, true), null, null, $user->getLanguage());

        return $data;
    }

    /**
     * @param string $key
     * @param int $count
     * @param string|null $name
     * @param string|null $roleKey
     * @param string|null $userLanguage
     * @return array|null
     */
    private function formatResponse(string $key, int $count, ?string $name = null, ?string $roleKey = null, ?string $userLanguage = 'en'): ?array
    {
        return ['roleKey' => $roleKey, 'name' => isset($name) ? $name : $this->translator->trans($key, [], null, $userLanguage), 'count' => $count, 'colour' => isset(Constants::ROLE_COLOUR[$key]) ? Constants::ROLE_COLOUR[$key] : null];
    }

    /**
     * @param string $currentUserRole
     * @param string|null $userLanguage
     * @return array
     */
    private function getDefaultDashboard(string $currentUserRole, ?string $userLanguage = 'en'): array
    {
        $data = [];
        $data['tickets'][] = $this->formatResponse($currentUserRole, 0, null, $currentUserRole, $userLanguage);
        $data['property'] = $this->formatResponse('properties', 0, null, null, $userLanguage);
        $data['object'] = $this->formatResponse('objects', 0, null, null, $userLanguage);
        $data['tenant'] = $this->formatResponse('activeTenants', 0, null, null, $userLanguage);
        $data['totalTickets'] = 0;

        return $data;
    }
}
