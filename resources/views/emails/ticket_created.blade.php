<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau ticket - {{ $ticket->subject }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px 20px;
        }
        .ticket-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .info-row {
            display: flex;
            margin-bottom: 10px;
            align-items: center;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
            min-width: 100px;
            margin-right: 10px;
        }
        .info-value {
            color: #333;
        }
        .priority {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .priority-high {
            background-color: #dc3545;
            color: white;
        }
        .priority-medium {
            background-color: #ffc107;
            color: #212529;
        }
        .priority-low {
            background-color: #28a745;
            color: white;
        }
        .description-box {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }
        .action-button {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
            transition: background-color: 0.3s ease;
        }
        .action-button:hover {
            background: #218838;
            color: white;
            text-decoration: none;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .footer {
            background: #f8f9fa;
            color: #6c757d;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            border-top: 1px solid #dee2e6;
        }
        .link-fallback {
            background: #f8f9fa;
            border: 1px dashed #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
            font-size: 12px;
            color: #6c757d;
            word-break: break-all;
        }
        .alert {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 12px;
            border-radius: 6px;
            margin: 15px 0;
            font-size: 14px;
        }
        .attachment-image {
            max-width: 100%;
            height: auto;
            margin: 10px 0;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🎫 Nouveau Ticket Support</h1>
            <p style="margin: 10px 0 0; opacity: 0.9;">Une nouvelle demande nécessite votre attention</p>
        </div>

        <!-- Content -->
        <div class="content">
            <p>Bonjour,</p>
            <p>Un nouveau ticket de support a été créé et nécessite votre intervention.</p>

            <!-- Ticket Information -->
            <div class="ticket-info">
                <div class="info-row">
                    <span class="info-label">Ticket #:</span>
                    <span class="info-value"><strong>{{ $ticket->id }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sujet:</span>
                    <span class="info-value"><strong>{{ $ticket->subject }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Priorité:</span>
                    <span class="info-value">
                        @php
                            $priorityClass = match($ticket->priority) {
                                'high' => 'priority-high',
                                'medium' => 'priority-medium',
                                'low' => 'priority-low',
                                default => 'priority-low'
                            };
                            $priorityIcon = match($ticket->priority) {
                                'high' => '🔴',
                                'medium' => '🟡',
                                'low' => '🟢',
                                default => '⚪'
                            };
                            $priorityLabel = match($ticket->priority) {
                                'high' => 'Élevée',
                                'medium' => 'Moyenne',
                                'low' => 'Faible',
                                default => ucfirst($ticket->priority)
                            };
                        @endphp
                        <span class="priority {{ $priorityClass }}">
                            {{ $priorityIcon }} {{ $priorityLabel }}
                        </span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Créé par:</span>
                    <span class="info-value">
                        @if ($ticket->user && $ticket->user->salarie)
                            {{ $ticket->user->salarie->prenom }} {{ $ticket->user->salarie->nom }} ({{ $ticket->user->email }})
                        @else
                            Utilisateur inconnu
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date:</span>
                    <span class="info-value">{{ $ticket->created_at->format('d/m/Y à H:i') }}</span>
                </div>
            </div>

            <!-- Description -->
            <h3 style="color: #495057; margin-bottom: 10px;">Description de la demande:</h3>
            <div class="description-box">
                {{ $ticket->description }}
            </div>

            <!-- Pièces jointes -->
            @if($ticket->attachments && $ticket->attachments->isNotEmpty())
                <h3 style="color: #495057; margin-bottom: 10px;">Pièces jointes :</h3>
                @foreach($ticket->attachments as $attachment)
                    <div>
                        <p><strong>{{ $attachment->file_name }}</strong></p>
                        @if(in_array($attachment->file_type, ['image/jpeg', 'image/png']))
                            <img src="{{ url('storage/' . $attachment->file_path) }}" alt="{{ $attachment->file_name }}" class="attachment-image">
                            <p><a href="{{ url('storage/' . $attachment->file_path) }}" target="_blank">Télécharger l'image</a></p>
                        @elseif(in_array($attachment->file_type, ['application/pdf']))
                            <a href="{{ url('storage/' . $attachment->file_path) }}" target="_blank">Télécharger le document</a>
                        @endif
                    </div>
                @endforeach
            @endif

            <!-- Action Button -->
            <div class="button-container">
                <a href="{{ route('tickets.close', ['id' => $ticket->id, 'token' => $ticket->close_token]) }}" 
                   class="action-button">
                    ✅ Marquer comme terminé
                </a>
            </div>

            <!-- Alert -->
            <div class="alert">
                <strong>Important:</strong> Ce lien est unique et expire le {{ $ticket->close_token_expires_at->format('d/m/Y à H:i') }}. 
                Ne le partagez avec personne d'autre.
            </div>

            <!-- Link fallback -->
            <p><strong>Si le bouton ne fonctionne pas</strong>, copiez et collez ce lien dans votre navigateur :</p>
            <div class="link-fallback">
                {{ route('tickets.close', ['id' => $ticket->id, 'token' => $ticket->close_token]) }}
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Ceci est un email automatique. Merci de ne pas répondre directement à ce message.</p>
            <p>© {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>