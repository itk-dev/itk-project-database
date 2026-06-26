<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\InitiativeRepository;
use App\Service\DashboardData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard', methods: ['GET'])]
    public function index(InitiativeRepository $initiatives, DashboardData $dashboardData): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'recent' => $initiatives->findRecent(8),
            'viz' => $dashboardData->build(),
        ]);
    }
}
