<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PagesController extends AbstractController
{
    #[Route('/hakkimizda', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('pages/about.html.twig');
    }

    #[Route('/destek', name: 'app_support')]
    public function support(): Response
    {
        return $this->render('pages/support.html.twig');
    }

    #[Route('/dokumantasyon', name: 'app_documentation')]
    public function documentation(): Response
    {
        return $this->render('pages/documentation.html.twig');
    }
}
