<?php

namespace App\Repository;

use App\Entity\Activite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activite>
 */
class ActiviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activite::class);
    }

    /** @return Activite[] */
    public function findAllOrderedByNom(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.nomActivite', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
