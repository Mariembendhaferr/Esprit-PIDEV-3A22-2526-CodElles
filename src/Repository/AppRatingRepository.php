<?php

namespace App\Repository;

use App\Entity\AppRating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AppRatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppRating::class);
    }

    public function hasUserRated(int $userId): bool
    {
        return (bool) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->join('a.user', 'u')
            ->where('u.idUser = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }
    public function getAverageRating(): float
{
    $result = $this->createQueryBuilder('a')
        ->select('AVG(a.rating)')
        ->getQuery()
        ->getSingleScalarResult();
    return round((float) $result, 1);
}

public function getTotalRatings(): int
{
    return $this->count([]);
}
}
