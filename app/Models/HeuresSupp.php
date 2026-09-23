<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeuresSupp extends Model
{
     protected $table = 'heuressupp';
    protected $fillable = [
        'secteur',
        'jour',
        'horaire_min',
        'horaire_max',
        'jr_ouvrable',
        'jr_feries',
    ];
}