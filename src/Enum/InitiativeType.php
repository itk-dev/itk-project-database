<?php

declare(strict_types=1);

namespace App\Enum;

enum InitiativeType: string implements TranslatableEnum
{
    case Project = 'project';
    case Programme = 'programme';
    case Policy = 'policy';
    case Pilot = 'pilot';
    case Operation = 'operation';

    public function labelKey(): string
    {
        return 'enum.initiative_type.'.$this->value;
    }
}
