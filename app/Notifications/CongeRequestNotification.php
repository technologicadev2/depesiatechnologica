<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Salarie;
use App\Models\Conge;

class CongeRequestNotification extends Notification
{
    use Queueable;

    protected $conge;
    protected $salarie;

    public function __construct(Conge $conge, Salarie $salarie)
    {
        $this->conge = $conge;
        $this->salarie = $salarie;
    }

    public function via($notifiable)
    {
        return ['database', 'mail']; // Notification stockée en base et envoyée par e-mail
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Nouvelle demande de congé')
            ->greeting('Bonjour,')
            ->line('Une nouvelle demande de congé a été soumise par ' . $this->salarie->nom . ' ' . $this->salarie->prenom . '.')
            ->line('Détails de la demande :')
            ->line('Date de début : ' . $this->conge->date_debut)
            ->line('Nombre de jours : ' . $this->conge->num_j)
            ->line('Raison : ' . $this->conge->raison)
            ->action('Voir la demande', url('/admin/conges/' . $this->conge->id))
            ->line('Merci de vérifier et d’approuver ou rejeter cette demande.');
    }

    public function toArray($notifiable)
    {
        return [
            'conge_id' => $this->conge->id,
            'salarie_id' => $this->salarie->id,
            'salarie_nom' => $this->salarie->nom . ' ' . $this->salarie->prenom,
            'date_debut' => $this->conge->date_debut,
            'nombre_jours' => $this->conge->num_j,
            'raison' => $this->conge->raison,
            'message' => 'Nouvelle demande de congé soumise par ' . $this->salarie->nom . ' ' . $this->salarie->prenom,
        ];
    }
}
