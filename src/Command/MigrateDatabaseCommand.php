<?php

namespace App\Command;

use App\Entity\Temp;
use App\Entity\User;
use App\Entity\UserIdentity;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class MigrateDatabaseCommand
 * @package App\Command
 */
class MigrateDatabaseCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'migrate:database';
    /**
     * @var string
     */
    protected static $defaultDescription = 'Migrate data from old database to new database';

    /**
     * @var
     */
    private $connection;

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
     * configure
     */
    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription);
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Exception
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $this->createConnection();
            $this->migrateData();
        } catch (\Exception $e) {
            $io->error($e->getMessage());            
        }

        return Command::SUCCESS;
    }

    /**
     * @return void
     */
    private function createConnection(): void
    {
        try {
            $config = new Configuration();
            $connectionParams = array(
                'dbname' => 'balu1',
                'user' => 'root',
                'password' => 'test123',
                'host' => 'database',
                'driver' => 'pdo_mysql',
            );
            $this->connection = DriverManager::getConnection($connectionParams, $config);
        } catch (Exception $e) {
            throw new \Exception($e->getMessage());           
        }
    }

    /**
     * migrateData
     */
    private function migrateData()
    {
        $this->migrateMasterData();
        $this->migrateUserData();
        $this->migrateRolePermissionData();
    }

    /**
     *
     */
    private function migrateMasterData(): void
    {
        $this->migrateObjectTypes('bp_object_types');
        $this->migrateCategory('bp_category');
        $this->migrateLandIndex('bp_land_index');
        $this->migrateRole('bp_role');
        $this->migrateCompanySubscriptionPlan('bp_company_subscription_plan');
        $this->migratePermission('bp_permission');
        $this->migrateSubscriptionPlan('bp_subscription_plan');
        $this->migrateReferenceIndex('bp_reference_index');
    }

    /**
     *
     */
    private function migrateUserData(): void
    {
        $users = $this->fetchData('bp_user');
        $newCount = $this->doctrine->getRepository(User::class)->getUserCount();
        $oldCount = count($users);
        if ($this->checkEntryCount($oldCount, $newCount)) {
            $entriesNeedUpdate = $this->getUpdateEntries('bp_user');
            $this->doctrine->getRepository(User::class)->insertUserData($users);
            $this->doctrine->getRepository(UserIdentity::class)->updateIdentitySelfJoinValues($entriesNeedUpdate);
            $this->doctrine->getRepository(UserIdentity::class)->migrateUserPermissions($this->fetchUserPermissionData('bp_user_permissions'));
            $this->doctrine->getRepository(UserIdentity::class)->migrateUserDevices($this->fetchData('bp_user_device'));
        }
    }

    /**
     * @param int $oldCount
     * @param int $newCount
     * @return bool
     */
    private function checkEntryCount(int $oldCount, int $newCount): bool
    {
        return $oldCount !== $newCount;
    }

    /**
     * @param string $table
     * @return array
     */
    private function fetchData(string $table): array
    {
        $data = array();
        $sql = "SELECT * FROM $table";
        $stmt = $this->connection->query($sql);
        while ($row = $stmt->fetch()) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * @param string $table
     */
    private function migrateObjectTypes(string $table): void
    {
        $objectTypes = $this->fetchData($table);
        $this->doctrine->getRepository(Temp::class)->insertObjectTypes($objectTypes);
    }

    /**
     * @param string $table
     */
    private function migrateCategory(string $table): void
    {
        $categories = $this->fetchData($table);
        $this->doctrine->getRepository(Temp::class)->insertCategories($categories);
    }

    /**
     * @param string $table
     */
    private function migrateLandIndex(string $table): void
    {
        $landIndices = $this->fetchData($table);
        $this->doctrine->getRepository(Temp::class)->insertLandIndices($landIndices);
    }

    /**
     * @param string $table
     */
    private function migrateRole(string $table): void
    {
        $roles = $this->fetchData($table);
        $this->doctrine->getRepository(Temp::class)->insertRoles($roles);
    }

    /**
     * @param string $table
     */
    private function migrateCompanySubscriptionPlan(string $table): void
    {
        $companySubscriptionPlans = $this->fetchData($table);
        $this->doctrine->getRepository(Temp::class)->insertCompanySubscriptionPlan($companySubscriptionPlans);
    }

    /**
     * @param string $table
     */
    private function migrateSubscriptionPlan(string $table): void
    {
        $subscriptionPlans = $this->fetchData($table);
        $this->doctrine->getRepository(Temp::class)->insertSubscriptionPlans($subscriptionPlans);
    }

    /**
     * @param string $table
     */
    private function migratePermission(string $table): void
    {
        $permissions = $this->fetchData($table);
        $this->doctrine->getRepository(Temp::class)->insertPermissions($permissions);
    }

    /**
     * @param string $table
     */
    private function migrateReferenceIndex(string $table): void
    {
        $referenceIndices = $this->fetchData($table);
        $this->doctrine->getRepository(Temp::class)->insertReferenceIndex($referenceIndices);
    }

    /**
     * @param string $table
     * @return array
     */
    private function getUpdateEntries(string $table): array
    {
        $data = array();
        $sql = "SELECT id, created_by, administrator, parent_id FROM $table WHERE created_by IS NOT NULL OR administrator IS NOT NULL OR parent_id IS NOT NULL";
        $stmt = $this->connection->query($sql);
        while ($row = $stmt->fetch()) {
            $data[] = $row;
        }
        return $data;
    }

    private function fetchUserPermissionData(string $table): array
    {
        $data = array();
        $sql = "SELECT p.id AS permissionId, p.key AS permissionKey, up.role_id, up.user_id, up.is_company, up.created_on, up.updated_on 
                FROM bp_permission p JOIN $table up ON up.permission_id = p.id";
        $stmt = $this->connection->query($sql);
        while ($row = $stmt->fetch()) {
            $data[] = $row;
        }
        return $data;
    }

    private function migrateRolePermissionData(): void
    {
        $data = array();
        $sql = "SELECT r.id, r.key, p.id AS permissionId, p.key AS permissionKey FROM bp_role_permission rp JOIN bp_role r ON r.id = rp.role_id JOIN bp_permission p ON p.id = rp.permission_id";
        $stmt = $this->connection->query($sql);
        while ($row = $stmt->fetch()) {
            $data[] = $row;
        }
        $this->doctrine->getRepository(Temp::class)->insertRolePermission($data);
    }
}
