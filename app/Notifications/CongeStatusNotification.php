<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Salarie;
use App\Models\Conge;

class CongeStatusNotification extends Notification
{
    use Queueable;

    protected $conge;
    protected $salarie;
    protected $status;

    public function __construct(Conge $conge, Salarie $salarie, $status)
    {
        $this->conge = $conge;
        $this->salarie = $salarie;
        $this->status = $status;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $statusText = $this->status == 2 ? 'acceptée' : 'refusée';
        return (new MailMessage)
            ->subject('Mise à jour de votre demande de congé')
            ->greeting('Bonjour ' . $this->salarie->prenom . ',')
            ->line('Votre demande de congé a été ' . $statusText . '.')
            ->line('Détails de la demande :')
            ->line('Date de début : ' . $this->conge->date_debut)
            ->line('Nombre de jours : ' . $this->conge->num_j)
            ->line('Raison : ' . $this->conge->raison)
            ->action('Voir les détails', url('/conges/' . $this->conge->id))
            ->line('Merci de contacter l\'administration pour toute question.');
    }

    public function toArray($notifiable)
    {
        $statusText = $this->status == 2 ? 'acceptée' : 'refusée';
        return [
            'conge_id' => $this->conge->id,
            'salarie_id' => $this->salarie->id,
            'salarie_nom' => $this->salarie->nom . ' ' . $this->salarie->prenom,
            'date_debut' => $this->conge->date_debut,
            'nombre_jours' => $this->conge->num_j,
            'raison' => $this->conge->raison,
            'status' => $this->status,
            'message' => 'Votre demande de congé a été ' . $statusText . '.',
        ];
    }
}
