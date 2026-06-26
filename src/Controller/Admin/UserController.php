<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('', name: 'admin_users', methods: ['GET'])]
    public function index(UserRepository $users): Response
    {
        return $this->render('admin/users/index.html.twig', [
            'users' => $users->findBy([], ['email' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): Response
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);
        $form = $this->createForm(UserType::class, $user, ['require_password' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();
            $user->setPassword($hasher->hashPassword($user, $plainPassword));
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'flash.user.created');

            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/users/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/edit', name: 'admin_user_edit', requirements: ['id' => Requirement::ULID], methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): Response
    {
        $form = $this->createForm(UserType::class, $user, ['require_password' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();
            if ('' !== $plainPassword) {
                $user->setPassword($hasher->hashPassword($user, $plainPassword));
            }
            $entityManager->flush();
            $this->addFlash('success', 'flash.user.updated');

            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/users/edit.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_user_delete', requirements: ['id' => Requirement::ULID], methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'flash.user.cannot_delete_self');

            return $this->redirectToRoute('admin_users');
        }

        if ($this->isCsrfTokenValid('delete-user-'.$user->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'flash.user.deleted');
        }

        return $this->redirectToRoute('admin_users');
    }
}
