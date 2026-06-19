<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;

final readonly class Paginator
{
    public const int PER_PAGE = 25;

    /**
     * @return PaginationResult<object>
     */
    public function paginate(QueryBuilder $queryBuilder, int $page, int $perPage = self::PER_PAGE): PaginationResult
    {
        $page = max(1, $page);

        $query = $queryBuilder->getQuery()
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        $paginator = new DoctrinePaginator($query, fetchJoinCollection: true);
        $total = \count($paginator);
        $pages = (int) max(1, ceil($total / $perPage));

        return new PaginationResult(
            items: array_values(iterator_to_array($paginator)),
            page: min($page, $pages),
            pages: $pages,
            total: $total,
            perPage: $perPage,
        );
    }
}
