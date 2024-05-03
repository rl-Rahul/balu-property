<?php


namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use App\Entity\ObjectContracts;
use App\Service\TenantService;
use App\Entity\Apartment;
use App\Service\ObjectService;
use App\Entity\ObjectContractDetail;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use App\Entity\UserIdentity;

/**
 * Class EntityListener
 * @package App\EventListener
 */
class EntityListener
{
    /**
     *
     * @var array
     */
    private array $audit = [];

    /**
     *
     * @var array
     */
    private array $objectLog = [];

    /**
     *
     * @var TenantService
     */
    private TenantService $tenantService;

    /**
     *
     * @var ObjectService
     */
    private ObjectService $objectService;

    /**
     *
     * @param TenantService $tenantService
     * @param ObjectService $objectService
     */
    public function __construct(TenantService $tenantService, ObjectService $objectService)
    {
        $this->tenantService = $tenantService;
        $this->objectService = $objectService;
    }

    /**
     *
     * @param PreUpdateEventArgs $eventArgs
     * @return void
     */
    public function preUpdate(PreUpdateEventArgs $eventArgs): void
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();
        $entity = $eventArgs->getEntity();
        if ($entity instanceof ObjectContracts) { // CONTRACT
            $uow->computeChangeSets();
            $changeSet = $uow->getEntityChangeSet($entity);
            if (count($changeSet) === 1 && (isset($changeSet['publicId']) || isset($changeSet['folder']))) { // if folder and publicid changed, no need to log
                return;
            }
            if ($changeSet) {
                $this->audit[] = $this->tenantService->updateContractHistory($entity, $changeSet);
            }
        }

        if ($entity instanceof Apartment) { // OBJECT
            $uow->computeChangeSets();
            $changeSet = $uow->getEntityChangeSet($entity);
            if (count($changeSet) === 1 && (isset($changeSet['publicId']) || isset($changeSet['folder']) || isset($changeSet['property']))) { // if folder and publicid changed, no need to log
                return;
            }
            if ($changeSet) {
                $logApartment = $this->audit[] = $this->objectService->updateObjectHistory($entity, $changeSet);
                $this->objectLog[] = (null !== $logApartment) ? $logApartment : null;
            }
        }

        if ($entity instanceof ObjectContractDetail) { // OBJECT detail
            $uow->computeChangeSets();
            $changeSet = $uow->getEntityChangeSet($entity);
            if (count($changeSet) === 1 && (isset($changeSet['publicId']) || isset($changeSet['object']))) { // if folder and publicid changed, no need to log
                return;
            }
            //save object log history
            if ($changeSet) {
                $this->audit[] = $this->objectService->updateObjectHistory($entity->getObject(), $changeSet, $this->objectLog, $entity);
            }

            if (isset($changeSet['netRentRate']) || isset($changeSet['additionalCost']) || isset($changeSet['referenceRate']) || isset($changeSet['landIndex']) || isset($changeSet['actualIndexStand']) || isset($changeSet['actualIndexStandNumber']) || isset($changeSet['modeOfPayment'])) {
                $this->audit[] = $this->objectService->updateRentHistory($entity, $changeSet);
            }

        }

        if ($entity instanceof UserIdentity) {
            $uow->computeChangeSets();
            $changeSet = $uow->getEntityChangeSet($entity);
            if ((isset($changeSet['firstName']) || isset($changeSet['lastName']))) {
                // check whether this is a pinned user, then update folder name also
                $this->tenantService->setFolderName($entity);
            }
        }

        return;
    }

    /**
     * @param PostFlushEventArgs $args
     * @throws
     *
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if (!empty($this->audit)) {
            $em = $args->getEntityManager();
            foreach ($this->audit as $audit) {
                if (is_array($audit)) {
                    foreach ($audit as $item) {
                        if (is_object($item)) {
                            $em->persist($item);
                        }
                    }
                }

                if (is_object($audit)) {
                    $em->persist($audit);
                }
            }

            $this->audit = [];
            $em->flush();
        }
    }
}