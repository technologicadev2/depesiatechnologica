<?php

namespace App\Models;

use Illuminate\Container\Attributes\Auth;
use Illuminate\Database\Eloquent\Model;

class Demission extends Model
{protected $table = 'demissions'; 
    protected $fillable = [
        'salarie_id',
        'date_demission',
        'date_fin_preavis',
        'motif',
        'document_path',
        'created_by',
'date_embauche'   ,
      'docPreavis',
 ];

    public function salarie()
    {
        return $this->belongsTo(Salarie::class);
    }

    public function creator()
    {
        return $this->belongsTo(Auth::class, 'created_by');
    }
     public function preavis()
    {
        return $this->hasOne(Preavis::class, 'salarie_id');
    }
}