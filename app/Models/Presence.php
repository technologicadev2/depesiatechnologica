<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presence extends Model
{
    protected $table = 'presence';

    protected $fillable = ['salarie_id', 'date', 'jour', 'heure', 'mois', 'statuts', 'id_projet', 'localisation','type_absence'];

    public function salarie()
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function projet()
    {
        return $this->belongsTo(Projet::class, 'id_projet');
    }
}
