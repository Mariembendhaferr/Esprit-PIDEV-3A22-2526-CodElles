<?php

namespace App\Repository;

use App\Entity\Activite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ActiviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activite::class);
    }

    public function searchAndFilter(?string $search, ?string $category, ?string $sort): array
    {
        $qb = $this->createQueryBuilder('a');

        if ($search) {
            $qb->andWhere('a.nomActivite LIKE :s OR a.localisationActivite LIKE :s OR a.categorieActivite LIKE :s')
               ->setParameter('s', '%' . $search . '%');
        }

        if ($category && $category !== 'all') {
            $qb->andWhere('a.categorieActivite = :cat')
               ->setParameter('cat', $category);
        }

        match ($sort) {
            'prix_asc'   => $qb->orderBy('a.coutActivite', 'ASC'),
            'prix_desc'  => $qb->orderBy('a.coutActivite', 'DESC'),
            'duree_asc'  => $qb->orderBy('a.dureeActivite', 'ASC'),
            'duree_desc' => $qb->orderBy('a.dureeActivite', 'DESC'),
            default      => $qb->orderBy('a.id', 'DESC'),
        };

        return $qb->getQuery()->getResult();
    }

    public function countByCategory(): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.categorieActivite as categorie, COUNT(a) as total')
            ->groupBy('a.categorieActivite')
            ->getQuery()->getResult();
    }
}