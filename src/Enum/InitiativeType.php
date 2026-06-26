<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * New cases' backing values must stay within the column length mapped on
 * {@see \App\Entity\Initiative}, or they will be truncated when persisted.
 */
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
