<?php
// src/Repository/CommunityPostRepository.php

namespace App\Repository;

use App\Entity\CommunityPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommunityPost>
 */
class CommunityPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommunityPost::class);
    }

    /**
     * Retourne les N derniers posts approuvés (pour la section communauté de l'accueil).
     *
     * @return CommunityPost[]
     */
    public function findLatestApproved(int $limit = 6): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.isVisible = true')
            ->andWhere('p.moderationStatus = :status')
            ->setParameter('status', 'approved')
            ->orderBy('p.approvedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre total de photos approuvées et visibles.
     *
     * @return int
     */
    public function countApprovedPhotos(): int
    {
        /** @var int|string|float|bool|null $result */
        $result = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.isVisible = true')
            ->andWhere('p.moderationStatus = :status')
            ->setParameter('status', 'approved')
            ->getQuery()
            ->getSingleScalarResult();
        
        return (int) $result;
    }

    /**
     * Compte le nombre de destinations uniques (distinctes) parmi les photos approuvées.
     *
     * @return int
     */
    public function countUniqueDestinations(): int
    {
        /** @var int|string|float|bool|null $result */
        $result = $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.destination)')
            ->where('p.isVisible = true')
            ->andWhere('p.moderationStatus = :status')
            ->setParameter('status', 'approved')
            ->getQuery()
            ->getSingleScalarResult();
        
        return (int) $result;
    }

    /**
     * Retourne les N destinations les plus partagées avec leur nombre de posts.
     * 
     * @return array<int, array{destination: string, cnt: int}>
     */
    public function findTopDestinations(int $limit = 3): array
    {
        /** @var array<int, array{destination: string, cnt: int}> $result */
        $result = $this->createQueryBuilder('p')
            ->select('p.destination, COUNT(p.id) as cnt')
            ->groupBy('p.destination')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        
        return $result;
    }
}