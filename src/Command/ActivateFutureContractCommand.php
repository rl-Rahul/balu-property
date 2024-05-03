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

class ActivateFutureContractCommand extends Command
{
    /**
     * 
     * @var string
     */
    protected static $defaultName = 'app:activate:future';
    
    /**
     * 
     * @var string
     */
    protected static $defaultDescription = 'Activate a future contract if there is no active contract';
    
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
     * @param LoggerInterface $activateFutureContractLogger
     */
    public function __construct(ManagerRegistry $doctrine, LoggerInterface $activateFutureContractLogger)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->logger = $activateFutureContractLogger;
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
            $futureContracts = $this->doctrine->getRepository(ObjectContracts::class)->getNextFutureContract($curDate);
            foreach($futureContracts as $contract) {
                $activeContractCount = $this->doctrine->getRepository(ObjectContracts::class)->checkForActiveContract($contract->getObject());
                if($activeContractCount === 0) { //if no active contract
                    $nextActiveContract = $this->doctrine->getRepository(ObjectContracts::class)->getNextFutureContract($curDate, $contract->getObject());
                    if($nextActiveContract->getStartDate()->format('Y-m-d') <= $curDate->format('Y-m-d')){
                        $nextActiveContract->setActive(true)
                                       ->setStatus(Constants::CONTRACT_STATUS_ACTIVE);
                    }
                }
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
