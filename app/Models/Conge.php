<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conge extends Model
{
    protected $table = 'conger';

    protected $fillable = [
        'salarie_id',
        'n_jours_reste',
        'num_j',
        'date_debut',
        'date_fin',
        'raison',
        'accepter',
        'signature',
        'pdf_path',
        'approbation'
    ];

    public function salarie()
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }
}