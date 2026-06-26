<?php

declare(strict_types=1);

namespace App\Enum;

enum Funding: string implements TranslatableEnum
{
    case MunicipalBudget = 'municipal_budget';
    case StateGrant = 'state_grant';
    case EuFunds = 'eu_funds';
    case Foundation = 'foundation';
    case ExternalPartner = 'external_partner';

    public function labelKey(): string
    {
        return 'enum.funding.'.$this->value;
    }
}
