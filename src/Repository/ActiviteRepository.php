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

    /**
     * Récupère les coordonnées d'une activité par son ID
     */
    public function findCoordinatesById(int $activiteId): ?array
    {
        $result = $this->createQueryBuilder('a')
            ->select('a.latitudeActivite', 'a.longitudeActivite')
            ->where('a.idActivite = :id')
            ->setParameter('id', $activiteId)
            ->getQuery()
            ->getOneOrNullResult();

        if ($result && $result['latitudeActivite'] && $result['longitudeActivite']) {
            return [
                'lat' => (float) $result['latitudeActivite'],
                'lng' => (float) $result['longitudeActivite']
            ];
        }
        
        return null;
    }

    /**
     * Récupère toutes les activités qui ont des coordonnées pour un voyage donné
     */
    public function findActivitesWithCoordinatesByVoyageId(int $voyageId): array
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.planjournaliers', 'p')
            ->innerJoin('p.voyage', 'v')
            ->where('v.id_voyage = :voyageId')
            ->andWhere('a.latitudeActivite IS NOT NULL')
            ->andWhere('a.longitudeActivite IS NOT NULL')
            ->setParameter('voyageId', $voyageId)
            ->orderBy('p.jour', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les activités filtrées par destination (localisationActivite LIKE %destination%)
     * Utilisé pour le menu déroulant du popup de modification du plan journalier.
     * Ex : destination = "France" → retourne toutes les activités localisées en France.
     */
public function findByDestination(string $destination): array
{
    return $this->createQueryBuilder('a')
        ->where('a.localisationActivite LIKE :destination')
        ->setParameter('destination', '%' . $destination . '%')
        ->orderBy('a.nomActivite', 'ASC')
        ->getQuery()
        ->getResult();
}
}
