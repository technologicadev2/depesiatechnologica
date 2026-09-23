<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class DefinitiveReceptionNotification extends Notification
{
    use Queueable;

    protected $project;

    public function __construct($project)
    {
        $this->project = $project;
    }

    public function via($notifiable)
    {
        return ['database']; // Stockage dans la base de données
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "La réception définitive du projet {$this->project['name']} est proche.",
            'reception_definitive' => $this->project['reception_definitive'],
            // 'url' => route('projects.show', $this->project['id']), // Route vers le projet
            'project_name' => $this->project['name'],
        ];
    }
}