<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestController extends AbstractController
{
    #[Route('/', name: 'app_test', methods: ['GET', 'POST'])]
    public function index(): Response
    {
        return $this->render('/dashboard.html.twig');
    }
}
