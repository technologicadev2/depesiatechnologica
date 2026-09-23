<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NatureDepense extends Model
{
    protected $table = 'nature_depences';
    protected $fillable = ['designation'];

    public function depences()
    {
        return $this->hasMany(Depences::class, 'nature_id', 'id');
    }
}
