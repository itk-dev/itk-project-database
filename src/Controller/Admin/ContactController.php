<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Contact;
use App\Form\ContactType;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/contacts')]
#[IsGranted('ROLE_USER')]
class ContactController extends AbstractController
{
    #[Route('', name: 'admin_contacts', methods: ['GET'])]
    public function index(ContactRepository $contacts): Response
    {
        return $this->render('admin/contacts/index.html.twig', [
            'contacts' => $contacts->findAllOrdered(),
        ]);
    }

    #[Route('/new', name: 'admin_contact_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($contact);
            $entityManager->flush();
            $this->addFlash('success', 'flash.contact.created');

            return $this->redirectToRoute('admin_contacts');
        }

        return $this->render('admin/contacts/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/edit', name: 'admin_contact_edit', requirements: ['id' => Requirement::ULID], methods: ['GET', 'POST'])]
    public function edit(Request $request, Contact $contact, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'flash.contact.updated');

            return $this->redirectToRoute('admin_contacts');
        }

        return $this->render('admin/contacts/edit.html.twig', [
            'form' => $form,
            'contact' => $contact,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_contact_delete', requirements: ['id' => Requirement::ULID], methods: ['POST'])]
    public function delete(Request $request, Contact $contact, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete-contact-'.$contact->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($contact);
            $entityManager->flush();
            $this->addFlash('success', 'flash.contact.deleted');
        }

        return $this->redirectToRoute('admin_contacts');
    }
}
