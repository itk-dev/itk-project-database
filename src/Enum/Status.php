<?php

declare(strict_types=1);

namespace App\Enum;

enum Status: string implements TranslatableEnum
{
    case Idea = 'idea';
    case Planned = 'planned';
    case Active = 'active';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function labelKey(): string
    {
        return 'enum.status.'.$this->value;
    }
}
