<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Activite;
use App\Entity\Avis;
use App\Entity\Reclamation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests manuels CRUD (Doctrine) pour Avis et Réclamation — alignés sur la logique des contrôleurs admin.
 *
 * Utilisation :
 * 1. Environnement test : base `doura_mondo_test` (suffixe `_test` ajouté par config Doctrine en APP_ENV=test).
 *    Créez-la et migrez : `php bin/console doctrine:database:create --env=test` puis `doctrine:migrations:migrate --env=test`
 *    (ou réutilisez les mêmes identifiants qu’en dev si vous avez déjà les tables).
 * 2. Décommentez UNE méthode test… (enlevez le commentaire autour du bloc concerné) puis lancez :
 *    `php vendor/bin/phpunit tests/CrudManualTest.php --filter testAvisCrudCycle`
 *    ou sans filtre pour tout ce qui est décommenté.
 *
 * Le test factice ci-dessous évite une suite « vide » tant que tout est commenté.
 */
class CrudManualTest extends KernelTestCase
{
    /** Garde la suite PHPUnit verte tant que les vrais tests CRUD restent commentés. */
    public function testPlaceholder(): void
    {
        self::assertTrue(true);
    }

    private function entityManager(): EntityManagerInterface
    {
        self::bootKernel();

        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function firstUser(EntityManagerInterface $em): User
    {
        $user = $em->getRepository(User::class)->findOneBy([]);
        self::assertNotNull($user, 'Ajoutez au moins un utilisateur en base pour ce test.');

        return $user;
    }

    private function firstActivite(EntityManagerInterface $em): Activite
    {
        $activite = $em->getRepository(Activite::class)->findOneBy([]);
        self::assertNotNull($activite, 'Ajoutez au moins une activité en base pour ce test.');

        return $activite;
    }

    /*
    public function testAvisCrudCycle(): void
    {
        $em = $this->entityManager();
        $user = $this->firstUser($em);
        $activite = $this->firstActivite($em);

        // Create (équivalent admin « nouveau » + persist)
        $avis = new Avis();
        $suffix = uniqid('crud_', true);
        $avis->setCommentaire('Commentaire de test CRUD manuel, au moins dix caractères. '.$suffix);
        $avis->setNote(4);
        $avis->setDateAvis(new \DateTimeImmutable());
        $avis->setUser($user);
        $avis->setActivite($activite);

        $em->persist($avis);
        $em->flush();
        $id = $avis->getId();
        self::assertNotNull($id);

        // Read
        $em->clear();
        $loaded = $em->find(Avis::class, $id);
        self::assertInstanceOf(Avis::class, $loaded);
        self::assertStringContainsString($suffix, $loaded->getCommentaire());

        // Update (équivalent admin « modifier » + flush)
        $loaded->setCommentaire('Mise à jour CRUD manuelle, dix chars min. '.$suffix);
        $loaded->setNote(5);
        $em->flush();
        $em->clear();
        $again = $em->find(Avis::class, $id);
        self::assertSame(5, $again->getNote());

        // Delete (équivalent admin « supprimer »)
        $em->remove($again);
        $em->flush();
        self::assertNull($em->find(Avis::class, $id));
    }
    */

    /*
    public function testReclamationCrudCycle(): void
    {
        $em = $this->entityManager();
        $user = $this->firstUser($em);

        // Create
        $rec = new Reclamation();
        $suffix = uniqid('crud_', true);
        $rec->setTitre('Titre test '.$suffix);
        $rec->setDescription('Description de test réclamation, plus de dix caractères. '.$suffix);
        $rec->setDateCreation(new \DateTimeImmutable());
        $rec->setStatut('En attente');
        $rec->setPriorite('Moyenne');
        $rec->setUser($user);

        $em->persist($rec);
        $em->flush();
        $id = $rec->getId();
        self::assertNotNull($id);

        // Read
        $em->clear();
        $loaded = $em->find(Reclamation::class, $id);
        self::assertInstanceOf(Reclamation::class, $loaded);
        self::assertStringContainsString($suffix, $loaded->getTitre());

        // Update
        $loaded->setStatut('En cours');
        $loaded->setPriorite('Élevée');
        $em->flush();
        $em->clear();
        $again = $em->find(Reclamation::class, $id);
        self::assertSame('En cours', $again->getStatut());

        // Delete
        $em->remove($again);
        $em->flush();
        self::assertNull($em->find(Reclamation::class, $id));
    }
    */
}
