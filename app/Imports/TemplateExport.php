<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TemplateExport implements FromArray, WithHeadings
{
    protected $type;

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function headings(): array
    {
        $baseHeaders = [
            'numero_facture',
            'date_facture',
            'raison_sociale',
            'ice',
            'taux_tva',
            'montant_tva',
            'montant_ht',
            'montant_ttc'
        ];

        if ($this->type === 'vente') {
            $baseHeaders[] = 'objet';
        }

        return $baseHeaders;
    }

    public function array(): array
    {
        return []; // Empty array for template (only headers)
    }
}