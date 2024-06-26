<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
class PaiementuserController extends AbstractController
{
    #[Route('/paiementuser', name: 'app_paiementuser')]
    public function index(): Response
    {
        return $this->render('paiementuser/index.html.twig', [
            'controller_name' => 'PaiementuserController',
        ]);
    }
}
