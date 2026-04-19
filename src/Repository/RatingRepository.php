<?php

namespace App\Repository;

use App\Entity\Rating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    public function hasRated(int $ratedUserId, int $raterUserId): bool
    {
        return (bool) $this->createQueryBuilder('r')
            ->select('COUNT(r.idRating)')
            ->join('r.ratedUser', 'ru')
            ->join('r.raterUser', 'ra')
            ->where('ru.idUser = :ratedId')
            ->andWhere('ra.idUser = :raterId')
            ->setParameter('ratedId', $ratedUserId)
            ->setParameter('raterId', $raterUserId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}