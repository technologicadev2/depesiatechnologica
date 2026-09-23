<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class SalProjet extends Pivot
{
    protected $table = 'sal_projet'; // Nom de la table pivot

    protected $fillable = [
        'projet_id',
        'salarie_id',
        'date_integration',
        'role',
        'date_sortie'
    ];
protected $casts = [
        'date_integration' => 'date',
        'date_sortie' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public $incrementing = true;
}
