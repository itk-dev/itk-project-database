<?php

declare(strict_types=1);

namespace App\Enum;

enum Category: string implements TranslatableEnum
{
    case Climate = 'climate';
    case Mobility = 'mobility';
    case Welfare = 'welfare';
    case Culture = 'culture';
    case Education = 'education';
    case Business = 'business';
    case Digitalisation = 'digitalisation';
    case UrbanDevelopment = 'urban_development';

    public function labelKey(): string
    {
        return 'enum.category.'.$this->value;
    }
}
