<?php

namespace App\Repository;

use App\Entity\Reclamation;
use App\Entity\ReclamationResponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReclamationResponse>
 */
class ReclamationResponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReclamationResponse::class);
    }

    /**
     * @param Reclamation[] $reclamations
     *
     * @return array<int, ReclamationResponse[]>
     */
    public function findGroupedByReclamations(array $reclamations): array
    {
        if ([] === $reclamations) {
            return [];
        }

        $rows = $this->createQueryBuilder('rr')
            ->leftJoin('rr.admin', 'a')->addSelect('a')
            ->leftJoin('rr.reclamation', 'r')->addSelect('r')
            ->andWhere('rr.reclamation IN (:recs)')
            ->setParameter('recs', $reclamations)
            ->orderBy('rr.dateResponse', 'DESC')
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($rows as $row) {
            $rid = $row->getReclamation()?->getId();
            if (null === $rid) {
                continue;
            }
            $grouped[$rid] ??= [];
            $grouped[$rid][] = $row;
        }

        return $grouped;
    }
}
