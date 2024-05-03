<?php

namespace App\Command;

use App\Entity\Payment;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\UserIdentity;
use Psr\Log\LoggerInterface;

/**
 * disableExpiredCompanyCommand
 *
 * Command class to set expired companies
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class DisableExpiredCompanyCommand extends Command
{
    /**
     * 
     * @var string
     */
    protected static $defaultName = 'app:disable:company';
    
    /**
     * 
     * @var string
     */
    protected static $defaultDescription = 'Disable Expired Company';  
    
    /**
     * 
     * @var string
     */
    protected static $defaultHelp ='This command allows you to disable expired companies';

    /**
     * @var ManagerRegistry
     */
    private ManagerRegistry $doctrine;
    
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    
    /**
     * MigrateDatabaseCommand constructor.
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine, LoggerInterface $disableCompanyLogger)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->logger = $disableCompanyLogger;
    }
    
    /**
     * 
     * @return void
     */
    protected function configure(): void
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
            'Updation Initialized....',
            'Please wait....',
        ));
    }

    /**
     * 
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $curDate = new \DateTime('now');
        $this->doctrine->getManager()->beginTransaction();
        try {
            $expiredCompanies = $this->doctrine->getRepository(UserIdentity::class)->getExpiredCompanies();
            foreach ($expiredCompanies as $companies) {
                if ($companies instanceof UserIdentity) {
                    $logs = $this->doctrine->getRepository(Payment::class)->findBy(['user' => $companies, 'isCompany' => true, 'event' => $companies->getStripeSubscription(), 'cancelledDate' => null]);
                    if (!empty($logs)) {
                        foreach ($logs as $log) {
                            $log->setExpiredAt($curDate);
                        }
                        $this->doctrine->getManager()->flush();
                    }
                    $companies->setIsExpired(true);
                    $companies->setExpiryDate($curDate);
                    $this->doctrine->getRepository(UserIdentity::class)->setCompanyUserStatus($companies);
                }
            }
            $this->doctrine->getManager()->flush();
            $this->doctrine->getManager()->commit();
            $this->logger->info("Company cron run successfully at ". $curDate->format("Y-m-d H:i:s"));
            $io->success(array('Updated Successfully'));
        } catch (\Exception $e) {
            $this->doctrine->getManager()->rollback();
            $this->logger->error($curDate->format("Y-m-d H:i:s") . ' ' . $e->getMessage());
            $io->error($e->getMessage());            
        }
        return Command::SUCCESS;
    }
}
