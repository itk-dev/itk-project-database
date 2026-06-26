<?php

declare(strict_types=1);

namespace App\Model;

use App\Enum\Category;
use App\Enum\InitiativeType;
use App\Enum\OrganizationalAnchoring;
use App\Enum\Status;

/**
 * Bound to the initiative list filter form (GET) and consumed by
 * {@see \App\Repository\InitiativeRepository::search()}.
 */
class InitiativeFilter
{
    public ?string $q = null;

    public ?Status $status = null;

    public ?Category $category = null;

    public ?InitiativeType $initiativeType = null;

    public ?OrganizationalAnchoring $organizationalAnchoring = null;

    public ?bool $endorsement = null;

    public ?int $budgetMin = null;

    public ?int $budgetMax = null;

    public string $sort = 'createdAt';

    public string $direction = 'DESC';
}
