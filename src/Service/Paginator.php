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
        $query = $queryBuilder->getQuery();
        $paginator = new DoctrinePaginator($query, fetchJoinCollection: true);

        // Count first so the requested page can be clamped to the valid range
        // *before* the offset query runs — otherwise ?page=999 issues a query
        // with a huge offset and returns an empty page.
        $total = \count($paginator);
        $pages = (int) max(1, ceil($total / $perPage));
        $page = min(max(1, $page), $pages);

        $query
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        return new PaginationResult(
            items: array_values(iterator_to_array($paginator)),
            page: $page,
            pages: $pages,
            total: $total,
            perPage: $perPage,
        );
    }
}
