<?php
namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use App\Entity\MailQueue;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Helpers\EmailServiceHelper;

/**
 * sendMailQueueCommand
 *
 * Command class to set expired companies
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class SendMailQueueCommand extends Command
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
     * @param ManagerRegistry $doctrine
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(ManagerRegistry $doctrine, ParameterBagInterface $parameterBag, EmailServiceHelper $emailHelper)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->parameterBag = $parameterBag;
        $this->emailHelper = $emailHelper;
    }
    
    /**
     *  Configure the console command
     *
     * @return void
     */
    protected function configure()
    {
        $this   
                ->setName('app:send:mail')
                ->setDescription('Send Mail From Queue')
                ->setHelp('Send Bulk Emails From Mail Queue')
                ->addArgument('no_email', InputArgument::REQUIRED, 'How many no of email do you want to send at a time ?');
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
            'Sending Mail',
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
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $number_of_mail = $input->getArgument('no_email');
        $fromMail = $this->parameterBag->get('from_email');
        $mails = $this->doctrine->getRepository(MailQueue::class)->findBy(
                    array(),
                    array(),
                    $number_of_mail
                );
        if(!empty($mails)){
            foreach ($mails as $mail) {
                if($mail instanceof MailQueue){
                   $this->emailHelper->sendEmail($mail->getSubject(), $mail->getBodyText(), $fromMail, $mail->getToMail()); 
                   $this->doctrine->getManager()->remove($mail); 
                }
            } 
        }
        $this->doctrine->getManager()->flush();
        $io->success(array('Mail Send Successfully'));
        
        return Command::SUCCESS;
    }
}
