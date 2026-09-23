<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotisations extends Model
{
    protected $table = 'cotisations';

    protected $fillable = [
        'cnss_pp',
        'amo_pp',

        'cnss_ps',
        'amo_ps', 
        'fp_ps',
        'ipe_ps',
        'plafond_ipe',
        'charge_de_famille',
        'plafond_cnss',
        'taux_CIMR',
        'taux_mutuelle'
    ];
}
