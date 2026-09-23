<?php

// app/Imports/FacturesImport.php
namespace App\Imports;

use App\Models\Entite;
use App\Models\FactureAchat;
use App\Models\FactureVente;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class FacturesImport implements ToModel, WithHeadingRow
{
    protected $type;

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function model(array $row)
    {
        // Débogage pour voir les données brutes (supprimez après test)
        // dd($row);

        // Normalisation de la date
        $dateFacture = null;
        $rawDate = trim($row['date_facture'] ?? '');

        if (!empty($rawDate)) {
            // Vérifier si c'est une date Excel (nombre)
            if (is_numeric($rawDate)) {
                try {
                    $dateFacture = Carbon::instance(ExcelDate::excelToDateTimeObject($rawDate));
                } catch (\Exception $e) {
                    $dateFacture = null;
                }
            }

            // Tenter de parser avec plusieurs formats si ce n'est pas une date Excel
            if (!$dateFacture) {
                $possibleFormats = [
                    'Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d', 'd.m.Y', 'Y.m.d',
                    'Y-m-d H:i:s', 'd/m/Y H:i:s', 'd-m-Y H:i:s'
                ];
                foreach ($possibleFormats as $format) {
                    try {
                        $dateFacture = Carbon::createFromFormat($format, $rawDate);
                        if ($dateFacture && $dateFacture->year >= 2000 && $dateFacture->year <= 2100) {
                            break;
                        }
                    } catch (\Exception $e) {
                        // Passer au format suivant
                    }
                }
            }

            // Tentative de parsing automatique si rien ne fonctionne
            if (!$dateFacture && !empty($rawDate)) {
                try {
                    $dateFacture = Carbon::parse($rawDate);
                } catch (\Exception $e) {
                    $dateFacture = null;
                }
            }
        }

        // Si la date est toujours invalide, utiliser la date actuelle avec heure
        if (!$dateFacture) {
            $dateFacture = Carbon::now()->setTime(rand(0, 23), rand(0, 59), rand(0, 59));
        }
        $dateFacture = $dateFacture->format('Y-m-d H:i:s');

        // Validation
        $validator = Validator::make($row, [
            'numero_facture' => 'required|string|unique:factures_' . $this->type,
            'date_facture' => 'required',
            'raison_sociale' => 'required|string',
            'ice' => 'required',
            'taux_tva' => 'required|numeric|min:0|max:100',
            'montant_tva' => 'required|numeric|min:0',
            'montant_ht' => 'required|numeric|min:0',
            'montant_ttc' => 'required|numeric|min:0',
            'objet' => $this->type === 'vente' ? 'nullable|string|max:255' : 'nullable',
        ]);

        if ($validator->fails()) {
            throw new \Exception(implode(', ', $validator->errors()->all()));
        }

        $entite = Entite::firstOrCreate(['ice' => (string)$row['ice']], ['raison_sociale' => $row['raison_sociale']]);

        $data = [
            'numero_facture' => $row['numero_facture'],
            'date_facture' => $dateFacture,
            'raison_sociale' => $row['raison_sociale'],
            'ice' => (string)$row['ice'],
            'taux_tva' => floatval($row['taux_tva']),
            'montant_tva' => floatval($row['montant_tva']),
            'montant_ht' => floatval($row['montant_ht']),
            'montant_ttc' => floatval($row['montant_ttc']),
        ];

        if ($this->type === 'vente') {
            $data['objet'] = $row['objet'] ?? null;
        }

        $model = $this->type === 'achat' ? FactureAchat::class : FactureVente::class;
        return new $model($data);
    }
}