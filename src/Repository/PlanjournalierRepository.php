<?php

namespace App\Repository;

use App\Entity\Planjournalier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ✅ Fix: générique spécifié
 * @extends ServiceEntityRepository<Planjournalier>
 */
class PlanjournalierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Planjournalier::class);
    }

    /** @return array<int, Planjournalier> */
    public function findPlanJournalierWithActiviteByVoyageId(int $voyageId): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.activite', 'a')
            ->addSelect('a')
            ->where('p.voyage = :voyageId')
            ->setParameter('voyageId', $voyageId)
            ->orderBy('p.jour', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return array<int, array<string, mixed>> */
    public function findPointsCarteByVoyageId(int $voyageId): array
    {
        $plans = $this->createQueryBuilder('p')
            ->leftJoin('p.activite', 'a')
            ->addSelect('a')
            ->where('p.voyage = :voyageId')
            ->andWhere('a.latitudeActivite IS NOT NULL')
            ->andWhere('a.longitudeActivite IS NOT NULL')
            ->setParameter('voyageId', $voyageId)
            ->orderBy('p.jour', 'ASC')
            ->getQuery()
            ->getResult();

        $points = [];
        foreach ($plans as $jour) {
            $activite = $jour->getActivite();
            if ($activite) {
                $points[] = [
                    'jour'         => $jour->getJour(),
                    'titre'        => $jour->getTitreJour(),
                    'nomActivite'  => $activite->getNomActivite(),
                    'lat'          => (float) $activite->getLatitudeActivite(),
                    'lng'          => (float) $activite->getLongitudeActivite(),
                    'description'  => $activite->getDescriptionActivite(),
                    'imageUrl'     => $activite->getImageActivite(),
                ];
            }
        }

        return $points;
    }
}