<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $table = 'vehicules';

    protected $fillable = [
        'matricule',
        'assurance_path',
        'assurance_expires_at',
        'marque',
        'visite_technique_path',
        'visite_technique_expires_at',
        'carte_grise_path',
        'carte_grise_expires_at',
        'contrat_achat_path',
        'contrat_achat_expires_at',
        'autres_documents',
          'resiliation_path',
    'resiliation_expires_at',
    'resilie',
    'type',
    'vignette_path',
    'vignette_expires_at'
    ];

  protected $casts = [
    'autres_documents' => 'array',
    'assurance_expires_at' => 'date',
    'visite_technique_expires_at' => 'date',
    'carte_grise_expires_at' => 'date', 
    'contrat_achat_expires_at' => 'date',
];

}