<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FraisProfessionnel extends Model
{
    protected $table = 'frais_professionnels';

    protected $fillable = [
        'sbi_min',
        'sbi_max',
        'taux',
        'somme_a_deduire',
             'plafond',
    ];
}
