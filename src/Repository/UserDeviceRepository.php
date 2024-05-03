<?php

namespace App\Repository;

use App\Entity\UserDevice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\UserIdentity;

class UserDeviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserDevice::class);
    }


    /**
     * Function to save User Device Details
     * @param string $deviceId
     * @param UserIdentity $user
     * @return bool
     */
    public function saveUserDeviceInfo(string $deviceId, UserIdentity $user): bool
    {
        $userDeviceObj = new UserDevice();
        $userDeviceObj->setDeviceId($deviceId);
        $userDeviceObj->setUser($user);
        $this->_em->persist($userDeviceObj);
        $this->_em->flush();

        return true;
    }

    /**
     * Function to get list of DeviceIds of User
     *
     * @param UserIdentity $user
     * @return array
     */
    public function userDeviceList(UserIdentity $user): array
    {
        $deviceIds = $this->createQueryBuilder('ud')
            ->select('ud.deviceId')
            ->where('ud.user = :user')
            ->andWhere('ud.deleted = :deleted AND ud.deviceId IS NOT NULL')
            ->setParameter('deleted', 0)
            ->setParameter('user', $user->getIdentifier());

        return array_filter(array_unique(array_column($deviceIds->getQuery()->getResult(), 'deviceId')));
    }
}
