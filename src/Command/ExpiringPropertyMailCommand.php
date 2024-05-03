<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Entity\Property;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Helpers\TemplateHelper;
use App\Entity\MailQueue;

/**
 * ExpiringPropertyMailCommand
 *
 * Command class to sent mail notification for expiring properties
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class ExpiringPropertyMailCommand extends Command
{
    /**
     * 
     * @var string
     */
    protected static $defaultName = 'app:expired:property';
    
    /**
     * 
     * @var string
     */
    protected static $defaultDescription = 'Expiring property';
    
    /**
     * 
     * @var string
     */
    protected static $defaultHelp = 'This command allows you to mail the owner regarding the expiring property';
    
    
    /**
     * @var ManagerRegistry
     */
    private ManagerRegistry $doctrine;
    
    /**
     * @var ParameterBagInterface $parameterBag
     */
    private ParameterBagInterface $parameterBag;
    
    /**
     * @var TranslatorInterface $translator
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
     * @return int
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $em = $this->doctrine->getManager();
        $expirationLimit = $this->parameterBag->get('expiration_limit');
        $expirationLimitFinal = $this->parameterBag->get('expiration_limit_final');
        $properties = $em->getRepository(Property::class)->getAllExpiringProperties($expirationLimit, $expirationLimitFinal);
        $planExpiryMailType = $this->parameterBag->get('expiry_mail_type');
        foreach ($properties as $property) {
            if ($property instanceof Property) {
                $owner = $property->getUser();
                $toMail = $owner->getUser()->getProperty();
                $userLanguage = ($owner->getLanguage()) ? $owner->getLanguage() : $this->parameterBag->get('default_language');
                $mailSubject = $this->translator->trans('mailPropertyExpiry', array(), null, $userLanguage);
                $template = $this->templateHelper->renderEmailTemplate('EmailPropertyExpiry', ['owner' => $owner,
                    'property' => $property,
                    'locale' => $userLanguage]);
                $mailQueue = new MailQueue();
                $mailQueue->setMailType($planExpiryMailType);
                $mailQueue->setSubject($mailSubject);
                $mailQueue->setBodyText($template);
                $mailQueue->setToMail($toMail);
                $mailQueue->setCreatedAt(new \DateTime());
                $em->persist($mailQueue);
            }
        }
        $em->flush();
        $io->success(array('Updated Successfully'));
        
        return Command::SUCCESS;
    }
}
