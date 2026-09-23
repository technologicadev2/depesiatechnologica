<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preavis extends Model
{
    protected $table = 'preavis';
    protected $fillable = [
        'demission_id',
        'date_debut',
        'date_fin',
        'document_path',
        'created_by',
        'salarie_id',
    ];

    public function demission()
    {
        return $this->belongsTo(Demission::class);
    }
    public function salarie()
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }
}