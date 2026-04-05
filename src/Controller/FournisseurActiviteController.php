<?php

namespace App\Controller;

use App\Entity\FournisseurActivite;
use App\Form\FournisseurActiviteType;
use App\Repository\FournisseurActiviteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ActiviteRepository;

#[Route('/fournisseur/activite')]
final class FournisseurActiviteController extends AbstractController
{
#[Route('/', name: 'app_fournisseur_activite_index', methods: ['GET'])]
public function index(Request $request, FournisseurActiviteRepository $repo): Response
{
    $search    = $request->query->get('search', '');
    $specialite = $request->query->get('specialite', '');
    $page      = max(1, (int)$request->query->get('page', 1));
    $itemsPerPage = 10;

    // Build query with filters
    $qb = $repo->createQueryBuilder('f');

    if ($search) {
        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->like('f.nomFournisseur',    ':search'),
                $qb->expr()->like('f.emailFournisseur',  ':search'),
                $qb->expr()->like('f.adresseFournisseur',':search'),
            )
        )->setParameter('search', '%' . $search . '%');
    }

    if ($specialite) {
        $qb->andWhere('f.specialiteFournisseur = :specialite')
           ->setParameter('specialite', $specialite);
    }

    $qb->orderBy('f.nomFournisseur', 'ASC');

    // Get total count
    $countQb = clone $qb;
    $total = count($countQb->getQuery()->getResult());
    $totalPages = ceil($total / $itemsPerPage);
    $page = min($page, max(1, $totalPages));

    // Apply pagination
    $fournisseurs = $qb->setFirstResult(($page - 1) * $itemsPerPage)
                        ->setMaxResults($itemsPerPage)
                        ->getQuery()
                        ->getResult();

    return $this->render('fournisseur_activite/index.html.twig', [
        'fournisseur_activites' => $fournisseurs,
        'search'                => $search,
        'specialite'            => $specialite,
        'currentPage'           => $page,
        'totalPages'            => $totalPages,
        'total'                 => $total,
    ]);
}

    #[Route('/new', name: 'app_fournisseur_activite_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $fournisseurActivite = new FournisseurActivite();
        $form = $this->createForm(FournisseurActiviteType::class, $fournisseurActivite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($fournisseurActivite);
            $entityManager->flush();

            return $this->redirectToRoute('app_fournisseur_activite_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('fournisseur_activite/new.html.twig', [
            'fournisseur_activite' => $fournisseurActivite,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/dashboard', name: 'app_fournisseur_activite_dashboard', methods: ['GET'])]
    public function dashboard(FournisseurActivite $fournisseur): Response
    {
        $activites = $fournisseur->getActivites();
    
        // ── Stats ────────────────────────────────────────────
        $totalActivites     = count($activites);
        $activitesAcceptees = 0;
        $revenuTotal        = 0;
        $dureeTotale        = 0;
        $parCategorie       = [];
        $derniereActivite   = null;
    
        foreach ($activites as $a) {
            if ($a->getStatutActivite() === 'acceptee') $activitesAcceptees++;
            $revenuTotal  += $a->getCoutActivite();
            $dureeTotale  += $a->getDureeActivite();
            $cat = $a->getCategorieActivite() ?? 'Autre';
            $parCategorie[$cat] = ($parCategorie[$cat] ?? 0) + 1;
            if (!$derniereActivite || $a->getId() > $derniereActivite->getId()) {
                $derniereActivite = $a;
            }
        }
    
        return $this->render('fournisseur_activite/dashboard.html.twig', [
            'fournisseur' => $fournisseur,
            'stats' => [
                'totalActivites'     => $totalActivites,
                'activitesAcceptees' => $activitesAcceptees,
                'revenuTotal'        => $revenuTotal,
                'dureeMoyenne'       => $totalActivites > 0 ? $dureeTotale / $totalActivites : 0,
                'parCategorie'       => $parCategorie,
                'derniereActivite'   => $derniereActivite,
            ],
        ]);
    }

    #[Route('/{id}', name: 'app_fournisseur_activite_show', methods: ['GET'])]
    public function show(FournisseurActivite $fournisseurActivite): Response
    {
        return $this->render('fournisseur_activite/show.html.twig', [
            'fournisseur_activite' => $fournisseurActivite,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_fournisseur_activite_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FournisseurActivite $fournisseurActivite, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FournisseurActiviteType::class, $fournisseurActivite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_fournisseur_activite_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('fournisseur_activite/edit.html.twig', [
            'fournisseur_activite' => $fournisseurActivite,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_fournisseur_activite_delete', methods: ['POST'])]
    public function delete(Request $request, FournisseurActivite $fournisseurActivite, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$fournisseurActivite->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($fournisseurActivite);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_fournisseur_activite_index', [], Response::HTTP_SEE_OTHER);
    }
}
