<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Initiative;
use App\Form\InitiativeFilterType;
use App\Form\InitiativeType;
use App\Model\InitiativeFilter;
use App\Repository\InitiativeRepository;
use App\Service\Paginator;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Writer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class InitiativeController extends AbstractController
{
    #[Route('/initiatives', name: 'app_initiative_index', methods: ['GET'])]
    public function index(Request $request, InitiativeRepository $initiatives, Paginator $paginator): Response
    {
        $filter = new InitiativeFilter();
        $form = $this->createForm(InitiativeFilterType::class, $filter);
        $form->handleRequest($request);

        $filter->sort = (string) $request->query->get('sort', 'createdAt');
        $filter->direction = (string) $request->query->get('direction', 'DESC');

        $pagination = $paginator->paginate(
            $initiatives->search($filter),
            $request->query->getInt('page', 1),
        );

        return $this->render('initiative/index.html.twig', [
            'form' => $form,
            'pagination' => $pagination,
            'sort' => $filter->sort,
            'direction' => $filter->direction,
        ]);
    }

    #[Route('/initiatives/export', name: 'app_initiative_export', methods: ['GET'])]
    public function export(Request $request, InitiativeRepository $initiatives, TranslatorInterface $translator): StreamedResponse
    {
        $filter = new InitiativeFilter();
        $this->createForm(InitiativeFilterType::class, $filter)->handleRequest($request);
        $filter->sort = (string) $request->query->get('sort', 'createdAt');
        $filter->direction = (string) $request->query->get('direction', 'DESC');

        /** @var Initiative[] $rows */
        $rows = $initiatives->search($filter)->getQuery()->getResult();

        $translate = static fn (?object $enum): string => $enum instanceof \App\Enum\TranslatableEnum
            ? $translator->trans($enum->labelKey())
            : '';

        $response = new StreamedResponse(function () use ($rows, $translator, $translate): void {
            $csv = Writer::createFromStream(fopen('php://output', 'w'));
            $csv->insertOne([
                'id', $translator->trans('initiative.title'), $translator->trans('initiative.status'),
                $translator->trans('initiative.category'), $translator->trans('initiative.initiative_type'),
                $translator->trans('initiative.organizational_anchoring'), $translator->trans('initiative.endorsement'),
                $translator->trans('initiative.endorsement_author'), $translator->trans('initiative.budget'),
                $translator->trans('initiative.funding'), $translator->trans('initiative.stakeholders'),
                $translator->trans('initiative.strategies'), $translator->trans('initiative.tags'),
                $translator->trans('initiative.time_period_start'), $translator->trans('initiative.time_period_end'),
                $translator->trans('initiative.contacts'), $translator->trans('initiative.author'),
            ]);

            $names = static fn (iterable $items): string => implode(', ', array_map('strval', \is_array($items) ? $items : iterator_to_array($items)));

            foreach ($rows as $row) {
                $csv->insertOne([
                    $row->getId(),
                    $row->getTitle(),
                    $translate($row->getStatus()),
                    $translate($row->getCategory()),
                    $translate($row->getInitiativeType()),
                    $translate($row->getOrganizationalAnchoring()),
                    $row->isEndorsement() ? $translator->trans('filter.yes') : $translator->trans('filter.no'),
                    $translate($row->getEndorsementAuthor()),
                    $row->getBudget(),
                    implode(', ', array_map($translate, $row->getFunding())),
                    $names($row->getStakeholders()),
                    $names($row->getStrategies()),
                    $names($row->getTags()),
                    $row->getTimePeriodStart()?->format('Y-m-d'),
                    $row->getTimePeriodEnd()?->format('Y-m-d'),
                    $names($row->getContacts()),
                    $row->getAuthor(),
                ]);
            }
        });

        $filename = sprintf('initiatives-%s.csv', (new \DateTimeImmutable())->format('Y-m-d'));
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));

        return $response;
    }

    #[Route('/initiatives/new', name: 'app_initiative_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $initiative = new Initiative();
        $form = $this->createForm(InitiativeType::class, $initiative);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->removeEmptyMedia($initiative);
            $entityManager->persist($initiative);
            $entityManager->flush();

            $this->addFlash('success', 'flash.initiative.created');

            return $this->redirectToRoute('app_initiative_show', ['id' => $initiative->getId()]);
        }

        return $this->render('initiative/new.html.twig', [
            'form' => $form,
            'initiative' => $initiative,
        ]);
    }

    #[Route('/initiatives/{id}', name: 'app_initiative_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Initiative $initiative): Response
    {
        return $this->render('initiative/show.html.twig', [
            'initiative' => $initiative,
        ]);
    }

    #[Route('/initiatives/{id}/edit', name: 'app_initiative_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Initiative $initiative, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(InitiativeType::class, $initiative);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->removeEmptyMedia($initiative);
            $entityManager->flush();

            $this->addFlash('success', 'flash.initiative.updated');

            return $this->redirectToRoute('app_initiative_show', ['id' => $initiative->getId()]);
        }

        return $this->render('initiative/edit.html.twig', [
            'form' => $form,
            'initiative' => $initiative,
        ]);
    }

    #[Route('/initiatives/{id}/delete', name: 'app_initiative_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Initiative $initiative, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete-initiative-'.$initiative->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($initiative);
            $entityManager->flush();
            $this->addFlash('success', 'flash.initiative.deleted');
        }

        return $this->redirectToRoute('app_initiative_index');
    }

    /**
     * Drop media rows the user added but left empty (no uploaded file).
     */
    private function removeEmptyMedia(Initiative $initiative): void
    {
        foreach ($initiative->getImages() as $image) {
            if (!$image->hasFile()) {
                $initiative->removeImage($image);
            }
        }

        foreach ($initiative->getAttachments() as $attachment) {
            if (!$attachment->hasFile()) {
                $initiative->removeAttachment($attachment);
            }
        }
    }
}
