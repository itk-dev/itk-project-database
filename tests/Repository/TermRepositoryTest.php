<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Enum\Vocabulary;
use App\Repository\TermRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TermRepositoryTest extends KernelTestCase
{
    private TermRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $repository = static::getContainer()->get(TermRepository::class);
        \assert($repository instanceof TermRepository);
        $this->repository = $repository;
    }

    public function testFindByVocabularyReturnsOnlyMatchingTerms(): void
    {
        $terms = $this->repository->findByVocabulary(Vocabulary::Tag);

        self::assertNotEmpty($terms);
        foreach ($terms as $term) {
            self::assertSame(Vocabulary::Tag, $term->getVocabulary());
        }
    }

    public function testFindOrCreateReturnsAnExistingTermCaseInsensitively(): void
    {
        // The fixtures create a "Klima" tag.
        $term = $this->repository->findOrCreate('klima', Vocabulary::Tag);

        self::assertSame('Klima', $term->getName());
        self::assertNotNull($term->getId());
    }

    public function testFindOrCreateBuildsANewUnflushedTerm(): void
    {
        $name = 'BrandNewTag-'.uniqid();

        $term = $this->repository->findOrCreate($name, Vocabulary::Strategy);

        self::assertSame($name, $term->getName());
        self::assertSame(Vocabulary::Strategy, $term->getVocabulary());
        self::assertNull($term->getId(), 'A freshly created term is persisted but not yet flushed.');
    }
}
