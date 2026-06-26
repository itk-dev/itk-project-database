<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * New cases' backing values must stay within the column length mapped on
 * {@see \App\Entity\Initiative}, or they will be truncated when persisted.
 */
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
