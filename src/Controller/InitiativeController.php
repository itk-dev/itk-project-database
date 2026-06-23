<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Initiative;
use App\Entity\User;
use App\Form\InitiativeFilterType;
use App\Form\InitiativeType;
use App\Model\InitiativeFilter;
use App\Repository\InitiativeRepository;
use App\Service\ActivityPublisher;
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
                $translator->trans('initiative.published'),
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
                    $row->getCreatedBy()?->getName(),
                    $row->isPublished() ? $translator->trans('filter.published') : $translator->trans('filter.draft'),
                ]);
            }
        });

        $filename = sprintf('initiatives-%s.csv', (new \DateTimeImmutable())->format('Y-m-d'));
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));

        return $response;
    }

    #[Route('/initiatives/new', name: 'app_initiative_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ActivityPublisher $activityPublisher): Response
    {
        // Autosave creates the initiative as soon as the form is valid; the page
        // then switches to editing it in place, so a new form saves like an edit.
        // Same guard as edit(): the mandatory X-Autosave header stands in for CSRF.
        $isAutosave = $request->headers->has('X-Autosave');

        $initiative = new Initiative();
        $form = $this->createForm(InitiativeType::class, $initiative, [
            'csrf_protection' => !$isAutosave,
            'allow_extra_fields' => $isAutosave,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->removeEmptyMedia($initiative);
            $entityManager->persist($initiative);
            $entityManager->flush();

            $activityPublisher->publish('created', $initiative, $this->currentUser());

            if ($isAutosave) {
                // Hand back the edit URL so the form keeps autosaving in place.
                $response = new Response(null, Response::HTTP_CREATED);
                $response->headers->set('X-Initiative-Location', $this->generateUrl('app_initiative_edit', ['id' => $initiative->getId()]));

                return $response;
            }

            $this->addFlash('success', 'flash.initiative.created');

            return $this->redirectToRoute('app_initiative_show', ['id' => $initiative->getId()]);
        }

        if ($isAutosave) {
            // Not valid yet (e.g. no title): report it without creating anything.
            return new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY);
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
    public function edit(Request $request, Initiative $initiative, EntityManagerInterface $entityManager, ActivityPublisher $activityPublisher): Response
    {
        // Autosave posts the same form via fetch; it expects to stay on the page.
        $isAutosave = $request->headers->has('X-Autosave');

        // A background fetch can't run the stateless-CSRF JS (no real submit), so the
        // sentinel token never resolves. Autosave is guarded instead by the mandatory
        // X-Autosave header — a cross-origin caller can't set it without a refused CORS
        // pre-flight — so we drop CSRF and ignore the now-stray _token field for it.
        $form = $this->createForm(InitiativeType::class, $initiative, [
            'csrf_protection' => !$isAutosave,
            'allow_extra_fields' => $isAutosave,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->removeEmptyMedia($initiative);
            $entityManager->flush();

            // Autosave is now the only save path on this form (the Save button is
            // gone), so it must drive the live feed and dashboard too. Repeated
            // autosaves of the same initiative don't flood the feed: each row is
            // keyed by id and bumped in place rather than stacked.
            $activityPublisher->publish('updated', $initiative, $this->currentUser());

            if ($isAutosave) {
                return new Response(null, Response::HTTP_NO_CONTENT);
            }

            $this->addFlash('success', 'flash.initiative.updated');

            return $this->redirectToRoute('app_initiative_show', ['id' => $initiative->getId()]);
        }

        if ($isAutosave) {
            // Report the failed validation without redrawing the form the user is editing.
            return new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->render('initiative/edit.html.twig', [
            'form' => $form,
            'initiative' => $initiative,
        ]);
    }

    #[Route('/initiatives/{id}/delete', name: 'app_initiative_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Initiative $initiative, EntityManagerInterface $entityManager, ActivityPublisher $activityPublisher): Response
    {
        if ($this->isCsrfTokenValid('delete-initiative-'.$initiative->getId(), (string) $request->request->get('_token'))) {
            $actor = $this->currentUser();

            $entityManager->remove($initiative);
            $entityManager->flush();

            // Publish after removal so the live dashboard counts are already up to
            // date; the detached entity still holds its title for the feed line.
            $activityPublisher->publish('deleted', $initiative, $actor);

            $this->addFlash('success', 'flash.initiative.deleted');
        }

        return $this->redirectToRoute('app_initiative_index');
    }

    private function currentUser(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
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
