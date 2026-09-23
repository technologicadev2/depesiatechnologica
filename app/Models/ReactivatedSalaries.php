<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReactivatedSalaries extends Model
{
    protected $fillable = [
        'original_salarie_id', 'new_salarie_id', 'original_cin', 'original_n_matricule_cnss',
        'modified_cin', 'modified_n_matricule_cnss', 'new_n_matricule_entreprise',
    ];

    public function originalSalarie()
    {
        return $this->belongsTo(Salarie::class, 'original_salarie_id');
    }

    public function newSalarie()
    {
        return $this->belongsTo(Salarie::class, 'new_salarie_id');
    }
}