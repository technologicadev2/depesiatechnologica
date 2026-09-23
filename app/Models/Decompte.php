<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Decompte extends Model
{
    use HasFactory;
    protected $table = 'decompte';
    protected $fillable = [
        'projet_id',
        'montant_dp',
        'revision_prix',
        'rg_dp',
        'date_dp',
        'type_decompte',
        'document_path', 
               'pourcentage', 
               'tranche'
      
    ];

    protected $casts = [
        'date_dp' => 'date',
        'reception_definitive' => 'date',
    ];

    public function projet()
    {
        return $this->belongsTo(Projet::class);
    }
        public function dossiersPdf()
    {
        return $this->hasMany(DossiersPdf::class, 'projet_id');
    }
}