<?php

namespace App\DataFixtures;

use App\Entity\Voyage;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $voyages = [
            ['Paris - Tour Eiffel & Musées',   7,  1200.00, 2],
            ['Rome - La Ville Éternelle',       5,   900.00, 4],
            ['Marrakech - Médina & Palais',     4,   600.00, 3],
            ['Istanbul - Entre deux continents',6,   850.00, 2],
            ['Barcelone - Art & Gastronomie',   8,  1400.00, 5],
            ['Dubai - Luxe & Désert',           6,  2000.00, 2],
            ['Tunis - Carthage & Sidi Bou Saïd',3,  400.00, 4],
        ];

        foreach ($voyages as [$destination, $duree, $budget, $nbPersonnes]) {
            $voyage = new Voyage();
            $voyage->setDestination($destination);
            $voyage->setDuree($duree);
            $voyage->setBudgetEstime($budget);    // prix total par personne
            $voyage->setNbPersonnes($nbPersonnes); // nb personnes recommandé
            $manager->persist($voyage);
        }

        $manager->flush();
        echo "✅ Voyages de test insérés avec succès.\n";
    }
}