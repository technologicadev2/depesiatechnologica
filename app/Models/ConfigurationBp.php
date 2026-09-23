<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConfigurationBp extends Model
{
    use HasFactory;

    protected $table = 'configurationbp';

    protected $fillable = [
        'pripanier',
        'indtransport',
        'prirepresentation',
        'prideplacement',
        'pridivers',
        'autrespriimposables',
        'cotisation_cimr',          // ← nom réel probable
        'indemnite_pe',             // ← nom réel probable
        'calculate_overtime',
        'cotisation_mutuelle',      // ← nom réel probable
        'apply_frais_pro',          // ← nom réel probable
        'apply_prime_rendement',
        'double_salary_holidays',
        'id_salarier',
    ];

    protected $casts = [
        'pripanier'             => 'decimal:2',
        'indtransport'          => 'decimal:2',
        'prirepresentation'     => 'decimal:2',
        'prideplacement'        => 'decimal:2',
        'pridivers'             => 'decimal:2',
        'autrespriimposables'   => 'decimal:2',
        'cotisation_cimr'       => 'boolean',          // ← changé ici
        'indemnite_pe'          => 'boolean',
        'calculate_overtime'    => 'boolean',
        'cotisation_mutuelle'   => 'boolean',
        'apply_frais_pro'       => 'boolean',
        'apply_prime_rendement' => 'boolean',
        'double_salary_holidays'=> 'boolean',
    ];
}