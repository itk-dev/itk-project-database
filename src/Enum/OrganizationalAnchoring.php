<?php

declare(strict_types=1);

namespace App\Enum;

enum OrganizationalAnchoring: string implements TranslatableEnum
{
    case MayorsDepartment = 'mayors_department';
    case TechnicalAndEnvironment = 'technical_and_environment';
    case CultureAndCitizens = 'culture_and_citizens';
    case SocialAndEmployment = 'social_and_employment';
    case ChildrenAndYouth = 'children_and_youth';
    case HealthAndCare = 'health_and_care';

    public function labelKey(): string
    {
        return 'enum.organizational_anchoring.'.$this->value;
    }
}
