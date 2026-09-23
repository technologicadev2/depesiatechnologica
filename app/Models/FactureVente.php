<?php
// Modèle FactureVente
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class FactureVente extends Model
{
    use HasFactory;

    protected $table = 'factures_vente';

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
        'objet',
        'date_encaissement',
        'encaisser',
        'relve_ex',
        'nm_jours'
    ];


// Dans votre accessor dans le Model
public function getNmJoursAttribute()
{
    return (int) Carbon::parse($this->date_facture)->diffInDays(Carbon::now());
}

    protected $casts = [
        // 'date_facture' => 'date',
        'montant_ht' => 'decimal:2',
        'taux_tva' => 'decimal:2',
        // 'montant_tva' => 'decimal:2',
        'montant_ttc' => 'decimal:2'
    ];
}