<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JourFerie extends Model
{
    protected $table = 'jour_feries';

    protected $fillable = [
        'date_debut',
        'date_fin',
        'nbr_jours',
        'nom',
        'religieux',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'nbr_jours' => 'integer',
    ];
}