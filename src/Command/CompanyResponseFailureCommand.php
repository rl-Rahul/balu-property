<?php

namespace App\Command;

use App\Service\SecurityService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\MailQueue;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\UserIdentity;
use App\Entity\UserDevice;
use App\Entity\Damage;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Helpers\EmailServiceHelper;
use App\Service\DamageService;
use App\Service\PushNotificationService;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Helpers\TemplateHelper;
use App\Utils\ContainerUtility;
use App\Service\UserService;

/**
 * DisablePropertiesCommand
 *
 * Command class to disable expired properties
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class CompanyResponseFailureCommand extends Command
{
    /**
     * @var ManagerRegistry
     */
    private ManagerRegistry $doctrine;
    
    
    /**
     * @var ParameterBagInterface $parameterBag
     */
    private ParameterBagInterface $parameterBag;
    
    /**
     * 
     * @var EmailServiceHelper
     */
    private EmailServiceHelper $emailHelper;
    
    /**
     * 
     * @var EmailServiceHelper
     */
    private DamageService $damageService;
    
    /**
     * 
     * @var EmailServiceHelper
     */
    private PushNotificationService $pushNotificationService;
    
    /**
     * @var SecurityService $securityService
     */
    private TranslatorInterface $translator;
    
    /**
     * @var TemplateHelper $templateHelper
     */
    private TemplateHelper $templateHelper;
    
    /**
     * @var ContainerUtility $containerUtility
     */
    private ContainerUtility $containerUtility;
    
    /**
     * @var UserService $userService
     */
    private UserService $userService;

    /**
     * @var SecurityService $securityService
     */
    private SecurityService $securityService;

    /**
     *
     * @param ManagerRegistry $doctrine
     * @param ParameterBagInterface $parameterBag
     * @param EmailServiceHelper $emailHelper
     * @param DamageService $damageService
     * @param PushNotificationService $pushNotificationService
     * @param TranslatorInterface $translator
     * @param TemplateHelper $templateHelper
     * @param ContainerUtility $containerUtility
     * @param UserService $userService
     * @param SecurityService $securityService
     */
    public function __construct(ManagerRegistry $doctrine, ParameterBagInterface $parameterBag,
                                EmailServiceHelper $emailHelper, DamageService $damageService,
                                PushNotificationService $pushNotificationService, TranslatorInterface $translator,
                                TemplateHelper $templateHelper, ContainerUtility $containerUtility,
                                UserService $userService, SecurityService $securityService)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->parameterBag = $parameterBag;
        $this->emailHelper = $emailHelper;
        $this->damageService = $damageService;
        $this->pushNotificationService = $pushNotificationService;
        $this->translator = $translator;
        $this->templateHelper = $templateHelper;
        $this->containerUtility = $containerUtility;
        $this->userService = $userService;
        $this->securityService = $securityService;
    }
    
    /**
     *  Configure the console command
     *
     * @return void
     */
    protected function configure()
    {
        $this
                ->setName('app:companyResponse:failure')
                ->setDescription('If company failed to respond within 24 hours, notificaion will send to company')
                ->setHelp('This command allows you to send notification to those companies which failed to respond to a damage ticket');
    }

    /**
     *  Initialize the console command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
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
        date_default_timezone_set("UTC");
        $io = new SymfonyStyle($input, $output);
        $statusArray = $this->parameterBag->get('damge_with_company_status');
        $maxDays = $this->parameterBag->get('company_response_pending_mail_repeat');
        /**
         * To get the current time
         */
        $curDateTime = new \DateTime('now');
        $curDateTime->setTimezone(new \DateTimeZone("UTC"));
        $curStrTime = strtotime($curDateTime->format('Y-m-d H:i:s'));
        
        /**
         * to get the time less than one hour
         * Cron job will run for each one hour
         */
        $minusOneDateTime = new \DateTime('now -1 hour');
        $minusOneDateTime->setTimezone(new \DateTimeZone("UTC"));
        $minusOneStrTime = strtotime($minusOneDateTime->format('Y-m-d H:i:s'));

        $companies = $this->doctrine->getRepository(UserIdentity::class)->getunResponsiveCompanyList($statusArray, $maxDays);
        $alertDays = [1,2,4,7,14,21,28]; //hours need to send mauil
        
        foreach ($companies as $companyArray) {

            $dateCompare =  $companyArray['compareTime']->setTimezone(new \DateTimeZone("UTC"));
            $dateCompare = \DateTimeImmutable::createFromMutable($dateCompare);
            foreach($alertDays as $alertDay) {
                $alertDate =  strtotime($dateCompare->modify("+$alertDay day")->format('Y-m-d H:i:s'));
                if($alertDate >= $minusOneStrTime && $alertDate <= $curStrTime) {
                    $company =  $this->doctrine->getRepository(UserIdentity::class)->findOneById($companyArray[0]['id']);
                    if ($company instanceof UserIdentity) {
                        $damage = $this->doctrine->getRepository(Damage::class)->findOneById($companyArray['damageId']);
                        $this->sendEmail($damage, $company);
                    }
                }
            }
        }
        
        $msg = array('Updated Successfully');
        $io->success($msg);
        
        return Command::SUCCESS;
    }

    /**
     * sendEmail
     *
     * @param Damage $bpDamage
     * @param UserIdentity $userObj
     * @return void
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    private function sendEmail(Damage $bpDamage, UserIdentity $userObj): void
    {
        $container = $this->parameterBag;
        $domain = $container->get('email_link_fe');
        $loginUrl =  $container->get('web_login_url');
        $loginUrl = ($domain) ? $domain.$loginUrl : $loginUrl;
        $companyDamageUrl =  $container->get('damage_view_url_company');
        $companyDamageUrl =  ($domain) ? $domain.$companyDamageUrl : $companyDamageUrl;
        $damageUrl =  $container->get('damage_view_url');
        $damageUrl =  ($domain) ? $domain.$damageUrl : $damageUrl;

        $toMail = $userObj->getUser()->getProperty();
        $data = $toMail.'#'.$bpDamage->getId().'#'.$bpDamage->getApartment()->getId();
        $token  = $this->containerUtility->encryptData($data, true, $container->get('token_expiry_hours'));
        $userLanguage = ($userObj->getLanguage()) ? $userObj->getLanguage() : $container->getParameter('locale');
        $mailSubject = $this->translator->trans('COMPANY_RESPONSE_PENDING_NOTIFICATION', array(), null, $userLanguage);
        $userRole = $this->securityService->fetchUserRole($userObj);
        $template = $this->templateHelper->renderEmailTemplate('EmailCompanyResponsePending', [
            'userFirstName' => $userObj->getFirstName(),
                'userLastName' => $userObj->getLastName(),
                'role' => $userRole,
                'loginUrl' => $loginUrl,
                'bpDamage' => $bpDamage,
                'locale' => $userLanguage,
                'apartment' => $bpDamage->getApartment(),
                'token' => $token,
                'companyDamageUrl' => $companyDamageUrl,
                'damageUrl' => $damageUrl        
            ]);
        $this->saveMailQue($mailSubject, $template, $toMail);
        $deviceIds =$this->getDeviceIds($userObj);
        $params = array('damage'=>$bpDamage, 'toUser'=>$userObj, 'message'=>$mailSubject);
        $notificationId =  $this->damageService->saveDamageNotification($params);
        if (!empty($deviceIds)) {
            $notificationParams = array("damageId" => $bpDamage->getId(), 'apartmentId' => $bpDamage->getApartment()->getId(),'userRole' => $userRole, "message"   =>  $mailSubject,
                                            "damageStatus" => $bpDamage->getStatus()->getKey(), 'notificationId' => $notificationId);
            $this->pushNotificationService->sendPushNotification($notificationParams, $deviceIds);
        }
    }

    /**
     * function to get user device id as array
     *
     * @param UserIdentity $userIdentity
     * @return array
     */
    private function getDeviceIds(UserIdentity $userIdentity): array
    {
        $deviceIds = [];
        $bpUserDevice = $this->doctrine->getRepository(UserDevice::class)->findBy(['user' => $userIdentity]);
        if (!empty($bpUserDevice)) {
            foreach ($bpUserDevice as $userDevice) {
                $deviceIds[] = $userDevice->getDeviceId();
            }
        }
            
        return $deviceIds;
    }

    /**
     * @param string $mailSubject
     * @param string $template
     * @param string $toMail
     */
    private function saveMailQue(string $mailSubject, string $template, string $toMail): void
    {
        $mailQueue = new MailQueue();
        $mailQueue->setMailType($this->parameterBag->get('company_response_pending_mail'));
        $mailQueue->setSubject($mailSubject);
        $mailQueue->setBodyText($template);
        $mailQueue->setToMail($toMail);
        $mailQueue->setCreatedAt(new \DateTime());
        $this->doctrine->getManager()->persist($mailQueue);
        $this->doctrine->getManager()->flush();
    }
}
