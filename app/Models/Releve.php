<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Releve extends Model
{
    protected $fillable = ['date', 'mois', 'annee', 'file_path','date_fin','credit','debit'];
    protected $casts = [
        'credit' => 'array',
        'debit'  => 'array',
    ];

}