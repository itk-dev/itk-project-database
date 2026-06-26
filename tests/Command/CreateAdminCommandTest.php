<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CreateAdminCommandTest extends KernelTestCase
{
    public function testCreatesANewAdministratorRetryingOnAShortPassword(): void
    {
        $tester = $this->commandTester();

        $email = sprintf('cli.admin.%s@example.com', uniqid());
        // The first password is too short (rejected by the validator), then a valid one.
        $tester->setInputs(['short', 'longenoughpassword']);
        $tester->execute(['email' => $email, 'name' => 'CLI Admin']);

        $tester->assertCommandIsSuccessful();

        $user = $this->users()->findOneBy(['email' => $email]);
        self::assertInstanceOf(User::class, $user);
        self::assertSame('CLI Admin', $user->getName());
        self::assertContains('ROLE_ADMIN', $user->getRoles());

        $this->remove($user);
    }

    public function testUpdatesAnExistingUserAndDefaultsNameToEmail(): void
    {
        $tester = $this->commandTester();

        $email = sprintf('cli.existing.%s@example.com', uniqid());
        $existing = (new User())
            ->setEmail($email)
            ->setName('Old Name')
            ->setRoles(['ROLE_USER'])
            ->setPassword('old-hash');
        $this->entityManager()->persist($existing);
        $this->entityManager()->flush();
        $existingId = (string) $existing->getId();

        $tester->setInputs(['anothergoodpassword']);
        $tester->execute(['email' => $email]);

        $tester->assertCommandIsSuccessful();

        $this->entityManager()->clear();
        $updated = $this->users()->findOneBy(['email' => $email]);
        self::assertInstanceOf(User::class, $updated);
        self::assertSame($existingId, (string) $updated->getId(), 'The existing user is updated, not duplicated.');
        self::assertSame($email, $updated->getName(), 'Without a name argument the e-mail is used.');
        self::assertContains('ROLE_ADMIN', $updated->getRoles());

        $this->remove($updated);
    }

    private function commandTester(): CommandTester
    {
        // bootKernel() returns the (single) booted kernel; the container is then
        // shared with the entities created in each test.
        $application = new Application(self::bootKernel());

        return new CommandTester($application->find('app:create-admin'));
    }

    private function users(): UserRepository
    {
        $repository = static::getContainer()->get(UserRepository::class);
        \assert($repository instanceof UserRepository);

        return $repository;
    }

    private function entityManager(): EntityManagerInterface
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);

        return $em;
    }

    private function remove(User $user): void
    {
        $this->entityManager()->remove($user);
        $this->entityManager()->flush();
    }
}
