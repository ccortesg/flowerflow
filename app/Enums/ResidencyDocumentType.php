<?php

namespace App\Enums;

enum ResidencyDocumentType: string
{
    case Identification = 'identification';
    case AddressProof = 'address_proof';
    case ResidenceCertificate = 'residence_certificate';
    case LeaseAgreement = 'lease_agreement';
    case Equivalent = 'equivalent';

    public function label(): string
    {
        return match ($this) {
            self::Identification => 'Identificación con domicilio en Hermosillo',
            self::AddressProof => 'Comprobante reciente de domicilio',
            self::ResidenceCertificate => 'Constancia de residencia',
            self::LeaseAgreement => 'Contrato de arrendamiento',
            self::Equivalent => 'Documento equivalente',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [$type->value => $type->label()])->all();
    }
}
