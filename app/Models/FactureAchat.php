<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class FactureAchat extends Model
{
    use HasFactory;

    protected $table = 'factures_achat';

    protected $fillable = [
        'numero_facture',
        'date_facture',
        'raison_sociale',
        'ice',
        'montant_ht',
        'taux_tva',
        'montant_tva',
        'montant_ttc',
        'file_path',
        'payee',
        'date_paiement',
        'releve_ex',
        'nm_jours'          // ← Ajouté
    ];

    protected $casts = [
        'montant_ht'  => 'decimal:2',
        'taux_tva'    => 'decimal:2',
        'montant_tva' => 'decimal:2',
        'montant_ttc' => 'decimal:2',
        'date_facture' => 'date',     // Bon à avoir
        'date_paiement' => 'date',
    ];

    /**
     * Accessor pour calculer automatiquement le nombre de jours écoulés
     */
    public function getNmJoursAttribute()
    {
        if (!$this->date_facture) {
            return 0;
        }
        return (int) Carbon::parse($this->date_facture)->diffInDays(Carbon::now());
    }
}