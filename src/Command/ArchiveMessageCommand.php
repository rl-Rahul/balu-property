<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\Persistence\ManagerRegistry;
use App\Service\DamageService;
use App\Entity\Damage;
use App\Entity\Message;
use App\Entity\DamageStatus;

class ArchiveMessageCommand extends Command
{
    /**
     * 
     * @var string
     */
    protected static $defaultName = 'app:archive:message';
    
    /**
     * 
     * @var string
     */
    protected static $defaultDescription = 'Archive all messages against closed tickets';
    
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
     * 
     * @param ManagerRegistry $doctrine
     * @param DamageService $damageService
     */
    public function __construct(ManagerRegistry $doctrine, DamageService $damageService)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->damageService = $damageService;
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
        try {
            $statusArray = $this->damageService->getDamageStatusArray('closed');
            foreach ($statusArray as $status) {
                $statusObj = $this->doctrine->getRepository(DamageStatus::class)->findOneBy(['key' => $status, 'deleted' => false]);
                $closedTickets = $this->doctrine->getRepository(Damage::class)->findBy(['status' => $statusObj, 'deleted' => false]);
                foreach($closedTickets as $closedTicket){
                    $message= $this->doctrine->getRepository(Message::class)->findOneBy(['damage' => $closedTicket, 'deleted' => false]);
                    if($message instanceof Message){
                        $this->doctrine->getRepository(Message::class)->setMessageArchived($message);
                    }
                }
            }
            $this->doctrine->getManager()->flush();
            $msg = array('Updated Successfully');
            $io->success($msg);
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }

        return Command::SUCCESS;
    }
}
