<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command;
use App\Service\DamageService;
use App\Entity\Damage;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Entity\User;
use App\Utils\ContainerUtility;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Utils\Constants;
use App\Helpers\TemplateHelper;
use App\Service\UserService; 
use App\Entity\UserIdentity;
use App\Service\PropertyService;
use App\Command\Traits\CommandTrait;


/**
 * ResponseFailureCommand
 *
 * Command class to notify response failures
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class ResponseFailureCommand extends Command
{
    use CommandTrait;
    
    /**
     * @var ManagerRegistry
     */
    private ManagerRegistry $doctrine;

    /**
     *
     * @var DamageService
     */
    private DamageService $damageService;

    /**
     * @var ParameterBagInterface $parameterBag
     */
    protected ParameterBagInterface $parameterBag;

    /**
     * @var ContainerUtility $containerUtility
     */
    private ContainerUtility $containerUtility;

    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    /**
     * @var TemplateHelper $templateHelper
     */
    private TemplateHelper $templateHelper;

    /**
     * @var UserService $userService
     */
    private UserService $userService;
    
    /**
     * @var PropertyService $propertyService
     */
    private PropertyService $propertyService;

    /**
     * @param ManagerRegistry $doctrine
     * @param DamageService $damageService
     * @param ParameterBagInterface $parameterBag
     * @param ContainerUtility $containerUtility
     * @param TranslatorInterface $translator
     * @param TemplateHelper $templateHelper
     * @param UserService $userService
     * @param PropertyService $propertyService
     */
    public function __construct(ManagerRegistry $doctrine, DamageService $damageService, ParameterBagInterface $parameterBag, ContainerUtility $containerUtility, TranslatorInterface $translator, TemplateHelper $templateHelper, UserService $userService, PropertyService $propertyService)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->damageService = $damageService;
        $this->parameterBag = $parameterBag;
        $this->containerUtility = $containerUtility;
        $this->translator = $translator;
        $this->templateHelper = $templateHelper;
        $this->userService = $userService;
        $this->propertyService = $propertyService;
        $this->mailType = $this->parameterBag->get('damage_response_pending_mail');
    }

    /**
     * Configure the console command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('damage:response:failure')
            ->setDescription('If an assignee failed to respond to a damage within 24 hours, notificaion will send to the assignee')
            ->setHelp('This command allows you to send notification to those assignees which failed to respond to a damage ticket');
      }

    /**
     * Initialize the console command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $io->note(array(
            'Updation Initialized....',
            'Please wait....',
        ));
    }

    /**
     * execute the console command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $assignedUsers = [];
        $io = new SymfonyStyle($input, $output);
        $em = $this->doctrine->getManager(); 
        $statusArray = $this->damageService->getDamageStatusArray('unresponsive');
        $maxDays = $this->parameterBag->get('response_pending_mail_repeat');
        $assignees = $em->getRepository(User::class)->getUnResponsiveDamageAssignees($statusArray, $maxDays);
        foreach ($assignees as $assignee) {
            $assignedUser = $this->getAssignedUser($assignee);
            if (null !== $assignedUser) {
                $assignedUsers[$assignee['damageId']] = $assignedUser;
            }
        }
        $this->notifyUsers($assignedUsers);
        $msg = array('Updated Successfully');
        $io->success($msg);

        return Command::SUCCESS;
    }
 
   /**
    * notifyUsers
    *
    * @param array $assignedUsers
    *
    * @return void
    */
    private function notifyUsers(array $assignedUsers): void
    {
        $em = $this->doctrine->getManager();
        $companyDamageUrl = $this->parameterBag->get('damage_view_url');
        $damageUrl = $this->parameterBag->get('damage_view_url');
        $locale = $this->parameterBag->get('locale');

        foreach ($assignedUsers as $damageId => $username) {
            $userObj = $em->getRepository(User::class)->findOneByProperty($username);
            $damageObj = $em->find(Damage::class, $damageId);
            $data = $username . '#' . $damageId . '#' . $damageObj->getApartment()->getId();
            $locale = ($userObj->getLanguage()) ? $userObj->getLanguage() : $locale;
            $userLanguage = ($userObj->getUserIdentity()->getLanguage()) ? $userObj->getUserIdentity()->getLanguage() : $this->parameterBag->get('locale');
            $mailSubject = $this->translator->trans('RESPONSE_PENDING_NOTIFICATION', [], null, $userLanguage);
            $damageUrl = ($damageObj->getAssignedCompany() === $userObj->getUserIdentity()) ? $companyDamageUrl : $damageUrl;
            $template = $this->templateHelper->renderEmailTemplate('Damage/ResponsePending', [
                'userFirstName' => $userObj->getFirstName(),
                'damage' => ['damage' => $damageObj, 'apartment' => $damageObj->getApartment()],
                'locale' => $locale,
                'token' => $this->containerUtility
                        ->encryptData($data, true, $this->parameterBag->get('token_expiry_hours') . '&lang=' . $userLanguage),
                'damageUrl' => $damageUrl,
                'damageCreatedBy' => $userObj->getFirstName() . ' ' . $userObj->getLastName()
            ]);
            $this->saveMailQueue($mailSubject, $template, $username);
            $this->damageService->sendPushNotification($userObj->getUserIdentity(), $damageObj, $mailSubject, ($damageObj->getAssignedCompany() === $userObj->getUserIdentity()) ? Constants::COMPANY_ROLE : '');
        }

        return;
    }
    
   /**
    * To find the assigned users
    *
    * @param array $assignee
    *
    * @return string
    */
    private function getAssignedUser(array $assignee)
    {   
        $assignedUser = null;
        if (str_contains('COMPANY_ACCEPTS_DAMAGE', $assignee['statusKey']) || str_contains('ACCEPTS_THE_OFFER', $assignee['statusKey'])) {
            $assignedUser = $this->getAssignedCompany($assignee);
        } elseif ($assignee['statusKey'] === 'COMPANY_SCHEDULE_DATE' || str_contains('COMPANY_GIVE_OFFER', $assignee['statusKey'])) {
            $assignedUser = $this->getOwner($assignee, true);
        } elseif (str_contains('_CREATE_DAMAGE', $assignee['statusKey'])) {
            $assignedUser = $this->getOwner($assignee);
        }

        return $assignedUser;
    }
    
    /**
     * To find apartment owner
     *
     * @param array $assignee
     * @param bool $damageOwner
     *
     * @return string
     */
    private function getOwner(array $assignee, $damageOwner = FALSE): ?string
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $damage = $em->getRepository(Damage::class)->findOneByIdentifier($assignee['damageId']);
        if ($damage instanceof Damage) {
            if ($damageOwner === TRUE) {
                $damageUserRole = $damage->getCreatedByRole()->getRoleKey();

                return (($damageUserRole === Constants::OWNER_ROLE) && ($damage->getUser()->getAdministrator() instanceof UserIdentity)) ? $damage->getUser()->getAdministrator()->getUsername() : $damage->getUser()->getUsername();
            }
            $hasActivePropertyAdmin = $this->propertyService->hasPropertyAdmin($damage->getApartment()->getProperty());

            return ($hasActivePropertyAdmin) ? $damage->getApartment()->getProperty()->getUser()->getAdministrator()->getUser()->getProperty() : $damage->getApartment()->getProperty()->getUser()->getUser()->getProperty();
        }

        return null;
    } 

    /**
     * To find assigned company
     *
     * @param array $assignee
     *
     * @return string
     */
    private function getAssignedCompany(array $assignee): ?string
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $damage = $em->getRepository(Damage::class)->findOneByIdentifier($assignee['damageId']);
        if ($damage instanceof Damage) {
            return $damage->getAssignedCompany()->getUser()->getProperty();
        }

        return null;
    } 
}
