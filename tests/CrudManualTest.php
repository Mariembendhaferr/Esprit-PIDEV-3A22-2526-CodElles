<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Activite;
use App\Entity\Avis;
use App\Entity\Reclamation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Depends;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;


class CrudManualTest extends KernelTestCase
{
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
        if (null !== $user) {
            return $user;
        }

        $suffix = uniqid('seed_', true);
        $user = new User();
        $user->setNom('Test');
        $user->setPrenom('Seed');
        $user->setUsername('seed_user_'.$suffix);
        $user->setEmail('seed_'.$suffix.'@example.test');
        $user->setMotDePasse('test');
        $user->setRole('voyageur');
        $user->setStatut('actif');
        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function firstActivite(EntityManagerInterface $em): Activite
    {
        $activite = $em->getRepository(Activite::class)->findOneBy([]);
        if (null !== $activite) {
            return $activite;
        }

        $owner = $this->firstUser($em);
        $activite = new Activite();
        $activite->setNomActivite('Activité seed '.uniqid());
        $activite->setCategorieActivite('test');
        $activite->setCoutActivite('0.00');
        $activite->setDisponibiliteActivite(true);
        $activite->setUser($owner);
        $em->persist($activite);
        $em->flush();

        return $activite;
    }

    // --- Avis : étape 1 Create → 2 Read → 3 Update → 4 Delete (chaîne #[Depends]) ---

    public function testAvisStep1_Create(): array
    {
        $em = $this->entityManager();
        $user = $this->firstUser($em);
        $activite = $this->firstActivite($em);

        $suffix = uniqid('crud_', true);
        $avis = new Avis();
        $avis->setCommentaire('Commentaire étape 1 CRUD, au moins dix caractères. '.$suffix);
        $avis->setNote(4);
        $avis->setDateAvis(new \DateTime());
        $avis->setUser($user);
        $avis->setActivite($activite);

        $em->persist($avis);
        $em->flush();

        $id = $avis->getId();
        self::assertNotNull($id);

        return ['id' => $id, 'suffix' => $suffix];
    }

    #[Depends('testAvisStep1_Create')]
    public function testAvisStep2_Read(array $ctx): array
    {
        $em = $this->entityManager();
        $em->clear();
        $loaded = $em->find(Avis::class, $ctx['id']);
        self::assertInstanceOf(Avis::class, $loaded);
        self::assertStringContainsString($ctx['suffix'], $loaded->getCommentaire());
        self::assertSame(4, $loaded->getNote());

        return $ctx;
    }

    #[Depends('testAvisStep2_Read')]
    public function testAvisStep3_Update(array $ctx): array
    {
        $em = $this->entityManager();
        $avis = $em->find(Avis::class, $ctx['id']);
        self::assertInstanceOf(Avis::class, $avis);
        $avis->setCommentaire('Mise à jour étape 3 CRUD, dix chars min. '.$ctx['suffix']);
        $avis->setNote(5);
        $em->flush();
        $em->clear();
        $again = $em->find(Avis::class, $ctx['id']);
        self::assertSame(5, $again->getNote());

        return $ctx;
    }

    #[Depends('testAvisStep3_Update')]
    public function testAvisStep4_Delete(array $ctx): void
    {
        $em = $this->entityManager();
        $avis = $em->find(Avis::class, $ctx['id']);
        self::assertInstanceOf(Avis::class, $avis);
        $em->remove($avis);
        $em->flush();
        self::assertNull($em->find(Avis::class, $ctx['id']));
    }

    // --- Réclamation : étapes 1–4 ---

    public function testReclamationStep1_Create(): array
    {
        $em = $this->entityManager();
        $user = $this->firstUser($em);

        $suffix = uniqid('crud_', true);
        $rec = new Reclamation();
        $rec->setTitre('Titre étape 1 '.$suffix);
        $rec->setDescription('Description étape 1 réclamation, plus de dix caractères. '.$suffix);
        $rec->setDateCreation(new \DateTime());
        $rec->setStatut('En attente');
        $rec->setPriorite('Moyenne');
        $rec->setUser($user);

        $em->persist($rec);
        $em->flush();

        $id = $rec->getId();
        self::assertNotNull($id);

        return ['id' => $id, 'suffix' => $suffix];
    }

    #[Depends('testReclamationStep1_Create')]
    public function testReclamationStep2_Read(array $ctx): array
    {
        $em = $this->entityManager();
        $em->clear();
        $loaded = $em->find(Reclamation::class, $ctx['id']);
        self::assertInstanceOf(Reclamation::class, $loaded);
        self::assertStringContainsString($ctx['suffix'], $loaded->getTitre());

        return $ctx;
    }

    #[Depends('testReclamationStep2_Read')]
    public function testReclamationStep3_Update(array $ctx): array
    {
        $em = $this->entityManager();
        $rec = $em->find(Reclamation::class, $ctx['id']);
        self::assertInstanceOf(Reclamation::class, $rec);
        $rec->setStatut('En cours');
        $rec->setPriorite('Élevée');
        $em->flush();
        $em->clear();
        $again = $em->find(Reclamation::class, $ctx['id']);
        self::assertSame('En cours', $again->getStatut());

        return $ctx;
    }

    #[Depends('testReclamationStep3_Update')]
    public function testReclamationStep4_Delete(array $ctx): void
    {
        $em = $this->entityManager();
        $rec = $em->find(Reclamation::class, $ctx['id']);
        self::assertInstanceOf(Reclamation::class, $rec);
        $em->remove($rec);
        $em->flush();
        self::assertNull($em->find(Reclamation::class, $ctx['id']));
    }
}
