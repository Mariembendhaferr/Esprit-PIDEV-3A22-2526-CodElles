<?php

namespace App\Tests\Service;

use App\Entity\Voyage;
use App\Service\VoyageManager;
use PHPUnit\Framework\TestCase;

class VoyageManagerTest extends TestCase
{
    // ✅ Test 1 : Voyage valide — toutes les règles respectées
    public function testValidVoyage()
    {
        $voyage = new Voyage();
        $voyage->setTitre('Découverte des merveilles de lEurope');
        $voyage->setContinent('Europe');
        $voyage->setDestination('France');
        $voyage->setBudgetEstime(1500.00);
        $voyage->setDuree(7);
        $voyage->setNbPersonnes(2);

        $manager = new VoyageManager();
        $this->assertTrue($manager->validate($voyage));
    }

    // ❌ Test 2 : Titre vide — doit lever une exception
    public function testVoyageWithoutTitre()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre est obligatoire.');

        $voyage = new Voyage();
        $voyage->setTitre('');
        $voyage->setContinent('Europe');
        $voyage->setDestination('France');
        $voyage->setBudgetEstime(1500.00);
        $voyage->setDuree(7);
        $voyage->setNbPersonnes(2);

        $manager = new VoyageManager();
        $manager->validate($voyage);
    }

    // ❌ Test 3 : Titre trop court (< 25 caractères) — doit lever une exception
    public function testVoyageWithTitreTropCourt()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre doit contenir au moins 25 caractères.');

        $voyage = new Voyage();
        $voyage->setTitre('Court');
        $voyage->setContinent('Europe');
        $voyage->setDestination('France');
        $voyage->setBudgetEstime(1500.00);
        $voyage->setDuree(7);
        $voyage->setNbPersonnes(2);

        $manager = new VoyageManager();
        $manager->validate($voyage);
    }

    // ❌ Test 4 : Continent invalide — doit lever une exception
    public function testVoyageWithContinentInvalide()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le continent saisi est invalide.');

        $voyage = new Voyage();
        $voyage->setTitre('Découverte des merveilles de lEurope');
        $voyage->setContinent('Antarctique');
        $voyage->setDestination('France');
        $voyage->setBudgetEstime(1500.00);
        $voyage->setDuree(7);
        $voyage->setNbPersonnes(2);

        $manager = new VoyageManager();
        $manager->validate($voyage);
    }

    // ❌ Test 5 : Destination ne correspond pas au continent — doit lever une exception
    public function testVoyageWithDestinationIncoherenteContinents()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La destination ne correspond pas au continent sélectionné.');

        $voyage = new Voyage();
        $voyage->setTitre('Découverte des merveilles de lEurope');
        $voyage->setContinent('Europe');
        $voyage->setDestination('Maroc'); // Maroc appartient à l'Afrique, pas l'Europe
        $voyage->setBudgetEstime(1500.00);
        $voyage->setDuree(7);
        $voyage->setNbPersonnes(2);

        $manager = new VoyageManager();
        $manager->validate($voyage);
    }

    // ❌ Test 6 : Budget négatif — doit lever une exception
    public function testVoyageWithBudgetInvalide()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le budget doit être supérieur à zéro.');

        $voyage = new Voyage();
        $voyage->setTitre('Découverte des merveilles de lEurope');
        $voyage->setContinent('Europe');
        $voyage->setDestination('France');
        $voyage->setBudgetEstime(-500.00);
        $voyage->setDuree(7);
        $voyage->setNbPersonnes(2);

        $manager = new VoyageManager();
        $manager->validate($voyage);
    }

    // ❌ Test 7 : Durée = 0 — doit lever une exception
    public function testVoyageWithDureeInvalide()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La durée doit être supérieure à zéro.');

        $voyage = new Voyage();
        $voyage->setTitre('Découverte des merveilles de lEurope');
        $voyage->setContinent('Europe');
        $voyage->setDestination('France');
        $voyage->setBudgetEstime(1500.00);
        $voyage->setDuree(0);
        $voyage->setNbPersonnes(2);

        $manager = new VoyageManager();
        $manager->validate($voyage);
    }

    // ❌ Test 8 : Nombre de personnes = 0 — doit lever une exception
    public function testVoyageWithNbPersonnesInvalide()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nombre de personnes doit être supérieur à zéro.');

        $voyage = new Voyage();
        $voyage->setTitre('Découverte des merveilles de lEurope');
        $voyage->setContinent('Europe');
        $voyage->setDestination('France');
        $voyage->setBudgetEstime(1500.00);
        $voyage->setDuree(7);
        $voyage->setNbPersonnes(0);

        $manager = new VoyageManager();
        $manager->validate($voyage);
    }
}