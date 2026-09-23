<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpotSurRevenu extends Model
{
    protected $table = 'impot_sur_revenus';

    protected $fillable = [
        'revenu_min',
        'revenu_max',
        'taux',
        'somme_a_deduire',
    ];
}
