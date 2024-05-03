<?php
namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Entity\Property;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Helpers\TemplateHelper;
use App\Entity\MailQueue;
use App\Entity\UserIdentity;

/**
 * ExpiringCompanyMailCommand
 *
 * Command class to sent mail notification for expiring companies
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class ExpiringCompanyMailCommand extends Command
{
    /**
     * 
     * @var string
     */
    protected static $defaultName = 'app:expiring:company';
    
    /**
     * 
     * @var string
     */
    protected static $defaultDescription = 'Expiring property';
    
    /**
     * 
     * @var string
     */
    protected static $defaultHelp = 'This command allows you to mail the company regarding the expiry';
    
    
    /**
     * @var ManagerRegistry
     */
    private ManagerRegistry $doctrine;
    
    /**
     * @var ParameterBagInterface $parameterBag
     */
    private ParameterBagInterface $parameterBag;
    
    /**
     * @var SecurityService $securityService
     */
    private TranslatorInterface $translator;
    
    /**
     * @var TemplateHelper $templateHelper
     */
    private TemplateHelper $templateHelper;
    
    /**
     * 
     * @param ManagerRegistry $doctrine
     * @param ParameterBagInterface $parameterBag
     * @param TranslatorInterface $translator
     * @param TemplateHelper $templateHelper
     */
    public function __construct(ManagerRegistry $doctrine, ParameterBagInterface $parameterBag, TranslatorInterface $translator, TemplateHelper $templateHelper)
    {
        parent::__construct();
        $this->parameterBag = $parameterBag;
        $this->doctrine = $doctrine;
        $this->translator = $translator;
        $this->templateHelper = $templateHelper;
    }
    
    /**
     *  Configure the console command
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription(self::$defaultDescription)
            ->setHelp(self::$defaultHelp);
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
            'Mail Queue Updating....',
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
        $io = new SymfonyStyle($input, $output);
        $em = $this->doctrine->getManager();
        $expirationLimit = $this->parameterBag->get('expiration_limit');
        $expirationLimitFinal = $this->parameterBag->get('expiration_limit_final');
        $companies = $em->getRepository(UserIdentity::class)->getAllExpiringCompanies($expirationLimit, $expirationLimitFinal);
        $planExpiryMailType = $this->parameterBag->get('company_expiry_mail_type');
        $batchSize = $this->parameterBag->get('batch_size');
        $count = 0;
        foreach ($companies as $company) {
            if ($company instanceof UserIdentity) {
                $userLanguage = ($company->getLanguage()) ? $company->getLanguage() : $this->parameterBag->get('default_language');
                $mailSubject = $this->translator->trans('mailCompanyExpiry', array(), null, $userLanguage);
                $template = $this->templateHelper->renderEmailTemplate('emailCompanyExpiry', ['companyName' => $company->getCompanyName(),
                    'locale' => $userLanguage]);
                $mailQueue = new MailQueue();
                $mailQueue->setMailType($planExpiryMailType);
                $mailQueue->setSubject($mailSubject);
                $mailQueue->setBodyText($template);
                $mailQueue->setToMail($company->getEmail());
                $mailQueue->setCreatedAt(new \DateTime());
                $em->persist($mailQueue);
                if (($count % $batchSize) === 0) {
                    $em->flush();
                    $em->clear();
                }
            }
            $count++;
        }
        $em->flush();
        $em->clear();
        $io->success(array('Updated Successfully'));
        
        return Command::SUCCESS;
    }
}
