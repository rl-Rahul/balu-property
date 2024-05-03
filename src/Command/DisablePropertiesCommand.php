<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Property;
use App\Entity\Apartment;
use Psr\Log\LoggerInterface;
use App\Entity\Payment;

/**
 * DisablePropertiesCommand
 *
 * Command class to disable expired properties
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class DisablePropertiesCommand extends Command
{
    /**
     * 
     * @var string
     */
    protected static $defaultName = 'app:disable:properties';
    
    /**
     * 
     * @var string
     */
    protected static $defaultDescription = 'Disable all expired properties';
    
    /**
     * 
     * @var string
     */
    protected static $defaultHelp = 'This command allows you to disable all the expired properties based on date';
    
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
     * @param LoggerInterface $disablePropertiesLogger
     */
    public function __construct(ManagerRegistry $doctrine, LoggerInterface $disablePropertiesLogger)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->logger = $disablePropertiesLogger;
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
        $io = new SymfonyStyle($input, $output);
        $curDate = new \DateTime('now');
        $em = $this->doctrine->getManager();
        $em->beginTransaction();
        try {
            $expiredProperties = $em->getRepository(Property::class)->getAllExpiredProperties();
            foreach ($expiredProperties as $property) {
                if ($property instanceof Property) {
                    $property->setActive(false);
                    $property->setExpiredDate($curDate->getTimestamp());
                    //disable their objects also
                    $em->getRepository(Apartment::class)->setObjectStatus($property, false);
                    $logs = $this->doctrine->getRepository(Payment::class)->findBy(['property' => $property, 'event' => $property->getStripeSubscription(), 'cancelledDate' => null]);
                    if (!empty($logs)) {
                        foreach ($logs as $log) {
                            $log->setExpiredAt($curDate);
                        }
                        $this->doctrine->getManager()->flush();
                    }
                }
            }
            $em->flush();
            $em->commit();
            $this->logger->info("Property cron run successfully at ". $curDate->format("Y-m-d H:i:s"));
            $io->success(array('Updated Successfully'));
        } catch (\Exception $e) {
            $em->rollback();
            $this->logger->error($curDate->format("Y-m-d H:i:s") . ' ' . $e->getMessage());
            $io->error($e->getMessage());            
        }
        return Command::SUCCESS;
    }
}
