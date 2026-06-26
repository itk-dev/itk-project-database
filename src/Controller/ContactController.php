<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contacts', name: 'app_contact_index', methods: ['GET'])]
    public function index(ContactRepository $contacts): Response
    {
        return $this->render('contact/index.html.twig', [
            'contacts' => $contacts->findAllOrdered(),
        ]);
    }

    #[Route('/contacts/new', name: 'app_contact_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($contact);
            $entityManager->flush();
            $this->addFlash('success', 'flash.contact.created');

            return $this->redirectToRoute('app_contact_index');
        }

        return $this->render('contact/new.html.twig', ['form' => $form]);
    }

    #[Route('/contacts/{id}/edit', name: 'app_contact_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Contact $contact, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'flash.contact.updated');

            return $this->redirectToRoute('app_contact_index');
        }

        return $this->render('contact/edit.html.twig', [
            'form' => $form,
            'contact' => $contact,
        ]);
    }

    #[Route('/contacts/{id}/delete', name: 'app_contact_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Contact $contact, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete-contact-'.$contact->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($contact);
            $entityManager->flush();
            $this->addFlash('success', 'flash.contact.deleted');
        }

        return $this->redirectToRoute('app_contact_index');
    }
}
