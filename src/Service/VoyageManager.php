<?php

namespace App\Service;

use App\Entity\Voyage;

class VoyageManager
{
    private const CONTINENTS_VALIDES = ['Afrique', 'Europe', 'Asie', 'Amériques', 'Océanie'];

    private const DESTINATIONS_PAR_CONTINENT = [
        'Afrique'   => ['Maroc', 'Tunisie', 'Égypte', 'Sénégal', 'Afrique du Sud', 'Kenya', 'Algérie'],
        'Europe'    => ['France', 'Italie', 'Espagne', 'Allemagne', 'Portugal', 'Grèce', 'Pays-Bas'],
        'Asie'      => ['Japon', 'Chine', 'Thaïlande', 'Inde', 'Vietnam', 'Corée du Sud', 'Turquie'],
        'Amériques' => ['États-Unis', 'Brésil', 'Canada', 'Mexique', 'Argentine', 'Colombie', 'Pérou'],
        'Océanie'   => ['Australie', 'Nouvelle-Zélande', 'Fidji', 'Papouasie', 'Samoa'],
    ];

    public function validate(Voyage $voyage): bool
    {
        // Règle 1 : Le titre est obligatoire et doit contenir au minimum 25 caractères
        if (empty($voyage->getTitre())) {
            throw new \InvalidArgumentException('Le titre est obligatoire.');
        }
        if (strlen($voyage->getTitre()) < 25) {
            throw new \InvalidArgumentException('Le titre doit contenir au moins 25 caractères.');
        }

        // Règle 2 : Le continent doit être valide
        if (!in_array($voyage->getContinent(), self::CONTINENTS_VALIDES, true)) {
            throw new \InvalidArgumentException('Le continent saisi est invalide.');
        }

        // Règle 3 : La destination doit appartenir au continent sélectionné
        $destinationsAutorisees = self::DESTINATIONS_PAR_CONTINENT[$voyage->getContinent()];
        if (!in_array($voyage->getDestination(), $destinationsAutorisees, true)) {
            throw new \InvalidArgumentException(
                'La destination ne correspond pas au continent sélectionné.'
            );
        }

        // Règle 4 : Le budget, la durée et le nombre de personnes doivent être > 0
        if ($voyage->getBudgetEstime() === null || $voyage->getBudgetEstime() <= 0) {
            throw new \InvalidArgumentException('Le budget doit être supérieur à zéro.');
        }
        if ($voyage->getDuree() === null || $voyage->getDuree() <= 0) {
            throw new \InvalidArgumentException('La durée doit être supérieure à zéro.');
        }
        if ($voyage->getNbPersonnes() === null || $voyage->getNbPersonnes() <= 0) {
            throw new \InvalidArgumentException('Le nombre de personnes doit être supérieur à zéro.');
        }

        return true;
    }
}