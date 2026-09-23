<?php

namespace App\Notifications;



use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Carbon\Carbon;


class DocumentExpiredNotification extends Notification
{
 use Queueable;

    protected $document;

    public function __construct($document)
    {
        $this->document = $document;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Alerte : Document Expiré')
            ->line('Le document suivant a expiré :')
            ->line('**Document** : ' . $this->document['label'])
            ->line('**Date d\'expiration** : ' . Carbon::parse($this->document['expires_at'])->format('d/m/Y'))
            ->line('**Projet** : ' . $this->document['project_name'])
            ->action('Voir le document', $this->document['file_path'] ? url('storage/' . $this->document['file_path']) : url('/parametres'))
            ->line('Veuillez prendre les mesures nécessaires pour renouveler ce document.');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'Le document "' . $this->document['label'] . '" a expiré le ' . Carbon::parse($this->document['expires_at'])->format('d/m/Y') . '.',
            'url' => $this->document['file_path'] ? url('storage/' . $this->document['file_path']) : url('/parametres'),
            'document_label' => $this->document['label'],
            'expires_at' => $this->document['expires_at'],
            'project_name' => $this->document['project_name'],
            'type' => 'document_expired', // Ajout d'un type pour identifier le type de notification
        ];
    }
}