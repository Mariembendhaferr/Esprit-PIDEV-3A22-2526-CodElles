<?php

namespace App\Repository;

use App\Entity\ReservationActivite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReservationActivite>
 *
 * @method ReservationActivite|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReservationActivite|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReservationActivite[]    findAll()
 * @method ReservationActivite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationActiviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReservationActivite::class);
    }

public function findByStaticUserAndPeriod(int $userId, \DateTimeInterface $start, \DateTimeInterface $end): array
{
    return $this->createQueryBuilder('r')
        ->andWhere('r.user = :userId') 
        ->andWhere('r.dateActivite BETWEEN :start AND :end')
        ->setParameter('userId', $userId)
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->getQuery()
        ->getResult();
}
}