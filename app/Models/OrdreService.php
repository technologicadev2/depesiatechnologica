<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdreService extends Model
{
    protected $table = 'ordres_service';
    protected $fillable = ['projet_id', 'type', 'date_ordre', 'document_path', 'created_by', 'updated_by','nbrjour',];
    protected $dates = ['date_ordre']; // Casts date_ordre to Carbon instance
    protected $casts = [
        'date_ordre' => 'datetime',
        'nbrjour' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    public function projet()
    {
        return $this->belongsTo(Projet::class);
    }
}