<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AncienneteTaux extends Model
{
    protected $table = 'anciennete_taux';

    protected $fillable = [
        'an_min',
        'an_max',
        'taux',
    ];
}