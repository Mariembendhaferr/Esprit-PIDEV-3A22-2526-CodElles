<?php

namespace App\Controller;

use App\Entity\CommunityPost;
use App\Form\CommunityPostType;
use App\Service\ImageModerationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class CommunityController extends AbstractController
{
    #[Route('/partager-photos', name: 'app_partager_photos', methods: ['GET', 'POST'])]
public function showForm(
    Request                $request,
    EntityManagerInterface $em,
    ImageModerationService $moderationService,
    SluggerInterface       $slugger,
): Response {
    $post = new CommunityPost();
    $form = $this->createForm(CommunityPostType::class, $post);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
        $file = $form->get('photo')->getData();

        $modResult = $moderationService->moderate($file->getPathname());

        /** @var string $projectDir */
        $projectDir = $this->getParameter('kernel.project_dir');
        $uploadDir = $projectDir . '/public/uploads/community';

        $safeOrig = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $fileName = $safeOrig . '-' . uniqid() . '.' . $file->guessExtension();

        $file->move($uploadDir, $fileName);
        $post->setImageUrl('/uploads/community/' . $fileName);

        if ($modResult['approved']) {
            $post->approve();
            $this->addFlash('success', 'Votre photo a été partagée avec la communauté !');
        } else {
            $post->reject(is_string($modResult['reason']) ? $modResult['reason'] : '');
            $this->addFlash('warning',
                'Votre image n\'a pas pu être publiée : ' . $modResult['reason'] .
                ' Merci de partager uniquement des photos de voyages ou de loisirs.'
            );
        }

        $em->persist($post);
        $em->flush();

        return $this->redirectToRoute('app_accueil');
    }

    return $this->render('voyage/partager_photos.html.twig', [
        'form'              => $form->createView(),
        'destinations_json' => json_encode(CommunityPostType::DESTINATIONS),
    ]);
}

    #[Route('/community/moderate-preview', name: 'app_community_moderate_preview', methods: ['POST'])]
    public function moderatePreview(
        Request                $request,
        ImageModerationService $moderationService,
    ): JsonResponse {
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $file */
        $file = $request->files->get('photo');

        if (!$file || !$file->isValid()) {
            return $this->json(['ok' => false, 'message' => 'Fichier invalide.'], 400);
        }

        $result = $moderationService->moderate($file->getPathname());

        return $this->json([
            'ok'      => $result['approved'],
            'message' => $result['reason'],
        ]);
    }
}