<?php

namespace App\Enums;

enum JudgeConflictType: string
{
    case PersonalOrFamilyRelationship = 'personal_or_family_relationship';
    case ProfessionalOrEconomicRelationship = 'professional_or_economic_relationship';
    case ParticipationInSubmission = 'participation_in_submission';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PersonalOrFamilyRelationship => 'Relación personal o familiar',
            self::ProfessionalOrEconomicRelationship => 'Relación profesional o económica',
            self::ParticipationInSubmission => 'Participación en la propuesta',
            self::Other => 'Otro conflicto',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
