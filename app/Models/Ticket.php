<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject',
        'description',
        'priority',

        'status',
        'close_token',
        'close_token_expires_at'
    ];

    protected $casts = [
        'close_token_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relation avec le modèle User
public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Méthodes utilitaires
    public function getPriorityLabelAttribute()
    {
        $labels = [
            'low' => 'Faible',
            'medium' => 'Moyenne',
            'high' => 'Élevée'
        ];
        
        return $labels[$this->priority] ?? ucfirst($this->priority);
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'open' => 'Ouvert',
            'in_progress' => 'En attente',
            'closed' => 'Terminé'
        ];
        
        return $labels[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function isClosed()
    {
        return $this->status === 'closed';
    }

    public function canBeClosed()
    {
        return !$this->isClosed();
    }
    public function attachments()
{
    return $this->hasMany(TicketAttachment::class);
}
}