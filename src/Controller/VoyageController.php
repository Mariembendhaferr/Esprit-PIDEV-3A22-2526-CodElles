<?php

namespace App\Controller;

use App\Repository\VoyageRepository;
use App\Repository\PlanjournalierRepository;  
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\Voyage; 
use Doctrine\ORM\EntityManagerInterface;
use App\Form\VoyageType;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class VoyageController extends AbstractController
{

    private $httpClient;
    
    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }



    // ==================== PAGE ACCUEIL ====================

    #[Route('/', name: 'app_accueil')]
    public function accueil(): Response
    {
        return $this->render('voyage/index.html.twig');
    }



    // ==================== PAGE DESTINATIONS ====================

    #[Route('/destinations', name: 'app_destinations')]
    public function destinations(): Response
    {
        return $this->render('voyage/destinations.html.twig');
    }



  






    

    // ==================== PAGE DETAIL D'UN VOYAGE ====================

    #[Route('/voyage/{id}', name: 'app_voyage_show')]
    public function show(int $id, VoyageRepository $voyageRepo, PlanjournalierRepository $planRepo): Response
    {
        $voyage = $voyageRepo->find($id);
        if (!$voyage) {
            throw $this->createNotFoundException('Voyage non trouvé');
        }
        $planJournaliers = $planRepo->findPlanJournalierWithActiviteByVoyageId($id);
        $pointsCarte = $planRepo->findPointsCarteByVoyageId($id);
        $galleryImages = $this->getUnsplashImages($voyage->getDestination(), 12);
        
        return $this->render('voyage/detail-voyage.html.twig', [
            'voyage' => $voyage,
            'planJournaliers' => $planJournaliers,
            'galleryImages' => $galleryImages,
            'pointsCarte' => $pointsCarte,
        ]);
    }




    



   


 
              





    // ==================== ADMIN LISTE DE VOYAGES ====================
    
    #[Route('/admin/voyages', name: 'app_voyage_admin')]
    public function voyages(VoyageRepository $repo): Response
    {
        $voyages = $repo->findAllForAdmin();
        $flags = [];
        foreach ($voyages as $voyage) {
            $flags[$voyage->getIdVoyage()] = $this->getFlagEmoji($voyage->getDestination());
        }
        
        return $this->render('admin/admin-liste.html.twig', [
            'active_menu' => 'voyages',
            'voyages' => $voyages,
            'flags' => $flags,   
        ]);
    }






    // ==================== ADMIN : FORMULAIRE AJOUTER (GET + POST) ====================

    #[Route('/admin/voyages/ajouter', name: 'app_voyage_ajouter')]
    public function ajouter(Request $request, EntityManagerInterface $entityManager): Response
    {
        $voyage = new Voyage();
        $form = $this->createForm(VoyageType::class, $voyage);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarder le voyage
            $entityManager->persist($voyage);
            $entityManager->flush();
            
            // Ajouter un message flash de succès
            $this->addFlash('success', '✅ Voyage "' . $voyage->getTitre() . '" créé avec succès !');
            
            // Rediriger vers planifier_sejour avec l'ID du voyage
            return $this->redirectToRoute('app_voyage_planifier', ['id' => $voyage->getIdVoyage()]);
        }
        
        return $this->render('admin/ajouter_voyage.html.twig', [
            'form' => $form->createView(),
        ]);
    }







    // ==================== ADMIN : PLANIFIER LE SÉJOUR ====================
 
    #[Route('/admin/voyages/planifier/{id}', name: 'app_voyage_planifier')]
    public function planifier(int $id, VoyageRepository $repo): Response
    {
        $voyage = $repo->find($id);
 
        if (!$voyage) {
            throw $this->createNotFoundException('Voyage introuvable.');
        }
 
        return $this->render('admin/planifier_sejour.html.twig', [
            'active_menu' => 'voyages',
            'voyage'      => $voyage,
        ]);
    }










    // ==================== ADMIN : SUPPRIMER UN VOYAGE ====================

#[Route('/admin/voyages/supprimer/{id}', name: 'app_voyage_supprimer', methods: ['POST'])]
public function supprimer(int $id, VoyageRepository $repo, EntityManagerInterface $em): JsonResponse
{
    $voyage = $repo->find($id);
    
    if (!$voyage) {
        return $this->json(['success' => false, 'message' => 'Voyage non trouvé'], 404);
    }
    
    try {
        $titre = $voyage->getTitre();
        $em->remove($voyage);
        $em->flush();
        
        return $this->json(['success' => true, 'message' => 'Voyage "' . $titre . '" supprimé avec succès']);
    } catch (\Exception $e) {
        return $this->json(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()], 500);
    }
}
}