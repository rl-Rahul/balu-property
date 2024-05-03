<?php


namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Entity\Damage;
use App\Entity\UserIdentity;
use App\Entity\MailQueue;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * DisablePropertiesCommand
 *
 * Command class to disable expired properties
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class ConfirmRepairMailCommand extends Command
{
    /**
     * 
     * @var string
     */
    protected static $defaultName = 'app:confirm:repair';
    
    /**
     * 
     * @var string
     */
    protected static $defaultDescription = 'Notification Mail For Confirming the Damage Repair';
    
    /**
     * 
     * @var string
     */
    protected static $defaultHelp = 'This command allows you to notify owner/tenant for confirm repair';
    
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
     * @param ManagerRegistry $doctrine
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(ManagerRegistry $doctrine, ParameterBagInterface $parameterBag)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->parameterBag = $parameterBag;
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
            'Sending Mail...',
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $numberOfMail = $this->parameterBag->get('repair_confirm_mail_repeat');
        $confirmStatus = $this->parameterBag->get('confirm_repair_status');
        $damages = $this->doctrine->getRepository(Damage::class)->pendingRepairConfirmationDamages($numberOfMail, $confirmStatus);
        $batchSize = $this->parameterBag->get('batch_size');
        $count = 0;
        foreach ($damages as $damage) {
            if ($damage instanceof Damage) {
                $user = $damage->getCompanyAssignedBy();
                if ($user instanceof UserIdentity) {
                    $damageUserRole = $this->getContainer()->get('baluproperty.user.services')->fetchUserRole($user);
                    $roleArray = $this->parameterBag->get('user_roles');
                    $user = (($damageUserRole == $roleArray['owner']) && ($user->getAdministrator() instanceof \AppBundle\Entity\BpUser)) ? $user->getAdministrator(): $user; 
                    
                    $userLanguage = ($user->getLanguage()) ? $user->getLanguage() : $this->parameterBag->get('locale');
                    $mailSubject = $this->getContainer()->get('translator')->trans('mailConfirmRepair', array(), null, $userLanguage);
                    $template = $this->getContainer()->get('templating')->render('AppBundle:Template:emailConfirmRepair.html.twig', array(
                        'damage' => $damage,
                        'locale' => $userLanguage,
                        'user' => $user
                    ));
                    $mailQueue = new MailQueue();
                    $mailQueue->setMailType($this->parameterBag->get('repair_confirm_mail'));
                    $mailQueue->setSubject($mailSubject);
                    $mailQueue->setBodyText($template);
                    $mailQueue->setToMail($user->getEmail());
                    $mailQueue->setCreatedAt(new \DateTime());
                            $this->doctrine->getManager()->persist($mailQueue);
                    if (($count % $batchSize) === 0) {
                        $this->doctrine->flush();
                        $this->doctrine->clear();
                    }
                }
            }
            $count++;
        }

        $this->doctrine->getManager()->flush();
        $this->doctrine->getManager()->clear();
        
        $io->success(array('Mailed Successfully'));
    }
}
