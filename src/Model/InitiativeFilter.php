<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Area;
use App\Entity\Department;
use App\Enum\InitiativeType;
use App\Enum\Status;

/**
 * Bound to the initiative list filter form (GET) and consumed by
 * {@see \App\Repository\InitiativeRepository::search()}.
 */
class InitiativeFilter
{
    public ?string $q = null;

    public ?Status $status = null;

    public ?Area $area = null;

    public ?InitiativeType $initiativeType = null;

    public ?Department $organizationalAnchoring = null;

    public ?bool $endorsement = null;

    public string $sort = 'createdAt';

    public string $direction = 'DESC';
}
