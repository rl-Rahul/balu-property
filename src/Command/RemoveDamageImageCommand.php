<?php

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\DamageImage;

/**
 * RemoveDamageImageCommand
 *
 * Command class to remove unassigned damage image
 *
 * @package         BaluProperty
 * @subpackage      AppBundle
 * @author          pitsolutions.ch
 */
class RemoveDamageImageCommand extends Command
{
    /**
     * 
     * @var string
     */
    protected static $defaultName = 'app:remove:damageImage';
    
    /**
     * 
     * @var string
     */
    protected static $defaultDescription = 'Remove unassigned damage image';
    
    /**
     * 
     * @var string
     */
    protected static $defaultHelp = 'This command allows you to remove unassigned damage image based on date';
    
    
    /**
     * @var ManagerRegistry
     */
    private ManagerRegistry $doctrine;

    /**
     * MigrateDatabaseCommand constructor.
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
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
        $curDate = new \DateTime('today');
        $documents = $this->doctrine->getRepository(DamageImage::class)->getDocumentsByDate($curDate);
        $this->doctrine->getManager()->beginTransaction();
        try {
            foreach ($documents as $document) {
                $filename = $document->getPath();
                if (file_exists($filename)) {
                    unlink($filename);
                }
                $this->doctrine->getManager()->remove($document);
            }
            $this->doctrine->getManager()->flush();
            $this->doctrine->getManager()->commit();
            $msg = array('Removed Successfully');
            $io->success($msg);
        } catch (\Exception $e) {
            $this->doctrine->getManager()->rollback();
            throw new \RuntimeException('Removal failed');
        }
        
        return Command::SUCCESS;
    }
}
