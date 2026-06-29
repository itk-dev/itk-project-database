<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\ContactRepository;
use App\Repository\InitiativeRepository;
use App\Service\DashboardData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard', methods: ['GET'])]
    public function index(InitiativeRepository $initiatives, ContactRepository $contacts, DashboardData $dashboardData): Response
    {
        $user = $this->getUser();

        return $this->render('dashboard/index.html.twig', [
            'recent' => $initiatives->findRecent(8),
            'viz' => $dashboardData->build(),
            'unfinishedInitiatives' => $user instanceof User ? $initiatives->findUnfinishedListByCreator($user) : [],
            'incompleteContacts' => $user instanceof User ? $contacts->findIncompleteListByCreator($user) : [],
        ]);
    }
}
