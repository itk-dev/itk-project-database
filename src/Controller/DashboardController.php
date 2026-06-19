<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\Status;
use App\Repository\ContactRepository;
use App\Repository\InitiativeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard', methods: ['GET'])]
    public function index(InitiativeRepository $initiatives, ContactRepository $contacts): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'total' => $initiatives->countAll(),
            'published' => $initiatives->countPublished(true),
            'drafts' => $initiatives->countPublished(false),
            'byStatus' => $initiatives->countByStatus(),
            'statuses' => Status::cases(),
            'recent' => $initiatives->findRecent(8),
            'contactCount' => $contacts->count([]),
        ]);
    }
}
