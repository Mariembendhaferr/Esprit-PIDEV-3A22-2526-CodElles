<?php

namespace App\Repository;

use App\Entity\Planjournalier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PlanjournalierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Planjournalier::class);
    }

    /**
     * Récupère tous les plans journaliers d'un voyage avec leurs activités (coordonnées incluses)
     * Triés par numéro de jour
     */
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




    /**
     * Récupère uniquement les points de la carte (coordonnées par jour) pour un voyage
     */
    public function findPointsCarteByVoyageId(int $voyageId): array
{
    $qb = $this->createQueryBuilder('p')
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
    foreach ($qb as $jour) {
        $activite = $jour->getActivite();
        if ($activite) {
            $points[] = [
                'jour' => $jour->getJour(),
                'titre' => $jour->getTitreJour(),
                'nomActivite' => $activite->getNomActivite(),
                'lat' => (float) $activite->getLatitudeActivite(),
                'lng' => (float) $activite->getLongitudeActivite(),
                'description' => $activite->getDescriptionActivite(),
                'imageUrl' => $activite->getImageActivite()  // ← AJOUTER L'IMAGE
            ];
        }
    }
    
    return $points;
}






}