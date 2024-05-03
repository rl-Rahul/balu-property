<?php

namespace App\Repository;

use App\Entity\MessageDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @method MessageDocument|null find($id, $lockMode = null, $lockVersion = null)
 * @method MessageDocument|null findOneBy(array $criteria, array $orderBy = null)
 * @method MessageDocument[]    findAll()
 * @method MessageDocument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageDocument::class);
    }

    /* @param MessageDocument $document
     * @return int|mixed|string
     * @throws
     */
    public function delete(MessageDocument $document)
    {
        $fs = new Filesystem();
        if ($fs->exists($document->getPath())) {
            $fs->remove($document->getPath());
        }
        $pathParts = pathinfo($document->getPath());
        $compressedFile = $pathParts['dirname'] . '/' . $pathParts['filename'] . '.zip';
        if ($fs->exists($compressedFile)) {
            $fs->remove($compressedFile);
        }
        $qb = $this->createQueryBuilder('d');
        return $qb
            ->update()
            ->set('d.deleted', ":deleted")
            ->where('d.identifier = :identifier')
            ->setParameters(['identifier' => $document->getIdentifier(), 'deleted' => true])
            ->getQuery()->getSingleScalarResult();
    }

}