<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorbeilleDepenses extends Model
{
    protected $table = 'corbeille_depenses';

    protected $fillable = [
        'code',
        'montant',
        'description',
        'date',
        'mois_depenses',
        'epreuve',
        'reglement_id',
        'reglement_depense',
        'nature_depense',
        'nature_id',
        'salarie',
        'salarie_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date' => 'date',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}