<?php

namespace App\Controller;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    /**
     * @return array
     */
    #[Route(path: '/dashboard', name: 'app_default', methods: 'GET')]
    #[Template('base.html.twig')]
    public function index(): array
    {
        return [];
    }

    #[Route(path: '/', name: 'app_landingpage', methods: 'GET')]
    #[Template('landingpage/landingpage.html.twig')]
    public function landingpage(): array
    {
        return [];
    }
}
