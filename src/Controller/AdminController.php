<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/utilisateurs', name: 'app_admin_utilisateurs')]
    public function utilisateurs(): Response
    {
        return $this->render('admin/utilisateurs.html.twig');
    }

    #[Route('/voyages', name: 'app_admin_voyages')]
    public function voyages(): Response
    {
        return $this->render('admin/voyages.html.twig');
    }

    #[Route('/activites', name: 'app_admin_activites')]
    public function activites(): Response
    {
        return $this->render('admin/activites.html.twig');
    }

    #[Route('/reservations', name: 'app_admin_reservations')]
    public function reservations(): Response
    {
        return $this->render('admin/reservations.html.twig');
    }

}
