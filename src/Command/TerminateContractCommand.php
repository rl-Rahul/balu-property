<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Entity\ObjectContracts;
use Doctrine\Persistence\ManagerRegistry;
use App\Utils\Constants;
use Psr\Log\LoggerInterface;
use App\Entity\PropertyUser;

class TerminateContractCommand extends Command
{
    /**
     * 
     * @var string
     */
    protected static $defaultName = 'app:terminate:contract';
    
    /**
     * 
     * @var string
     */
    protected static $defaultDescription = 'Terminate contract over a time period';
    
    /**
     * @var ManagerRegistry
     */
    private ManagerRegistry $doctrine;
    
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    
    /**
     * 
     * @param ManagerRegistry $doctrine
     * @param LoggerInterface $terminateContractLogger
     */
    public function __construct(ManagerRegistry $doctrine, LoggerInterface $terminateContractLogger)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->logger = $terminateContractLogger;
    }
    
    /**
     * 
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription(self::$defaultDescription);
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
        try {
            $activeContracts = $this->doctrine->getRepository(ObjectContracts::class)->getActiveContractByDate($curDate);
            foreach ($activeContracts as $contract) {
                $futureContract = $this->doctrine->getRepository(ObjectContracts::class)->getFutureContractByApartment($contract->getObject(), $curDate);
                $contract->setActive(false)
                         ->setStatus(Constants::CONTRACT_STATUS_ARCHIVED);
                
                if(is_null($contract->getTerminationDate())){
                    $contract->setTerminationDate($contract->getEndDate());
                }
                if ($futureContract instanceof ObjectContracts) {
                    $futureContract->setActive(true)
                                   ->setStatus(Constants::CONTRACT_STATUS_ACTIVE);
                }
                
                $this->doctrine->getRepository(PropertyUser::class)->disableContractUsers($contract);
            }
            $this->doctrine->getManager()->flush();
            $this->logger->info("Contract cron run successfully at ". $curDate->format("Y-m-d H:i:s"));
            $msg = array('Updated Successfully');
            $io->success($msg);
        } catch (\Exception $e) {
            $this->logger->error($curDate->format("Y-m-d H:i:s") . ' ' . $e->getMessage());
            $io->error($e->getMessage());
        }

        return Command::SUCCESS;
    }
}
