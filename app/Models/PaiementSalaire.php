<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaiementSalaire extends Model
{
    protected $table = 'paiement_salaires';
    protected $fillable = ['annee', 'mois', 'salaire', 'id_salarie'];

    public function salarie()
    {
        return $this->belongsTo(Salarie::class, 'id_salarie');
    }
}