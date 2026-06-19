<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Backed enums whose cases carry a translatable, human-readable label.
 *
 * The enum value is the single source of truth stored on the entity and
 * exposed on the API; labelKey() routes the UI through the translation
 * catalogues so labels stay bilingual (en/da).
 */
interface TranslatableEnum
{
    public function labelKey(): string;
}
