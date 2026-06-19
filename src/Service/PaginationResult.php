<?php

declare(strict_types=1);

namespace App\Service;

/**
 * @template T
 */
final readonly class PaginationResult
{
    /**
     * @param list<T> $items
     */
    public function __construct(
        public array $items,
        public int $page,
        public int $pages,
        public int $total,
        public int $perPage,
    ) {
    }

    public function hasPrevious(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->pages;
    }

    public function firstResult(): int
    {
        return 0 === $this->total ? 0 : (($this->page - 1) * $this->perPage) + 1;
    }

    public function lastResult(): int
    {
        return min($this->page * $this->perPage, $this->total);
    }
}
