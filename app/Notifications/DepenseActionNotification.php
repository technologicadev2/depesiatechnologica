<?php

namespace App\Notifications;

use App\Models\Depences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DepenseActionNotification extends Notification
{
    use Queueable;

    public $depense;
    public $action;

    /**
     * Create a new notification instance.
     */
    public function __construct(Depences $depense, $action)
    {
        $this->depense = $depense;
        $this->action = $action;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database']; // Stocke les notifications dans la base de données
    }

    /**
     * Get the array representation of the notification for the database.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'depense_id' => $this->depense->id,
            'code' => $this->depense->code,
            'montant' => $this->depense->montant,
            'action' => $this->action,
            'nature_depense' => $this->depense->nature_depense,
        ];
    }
}