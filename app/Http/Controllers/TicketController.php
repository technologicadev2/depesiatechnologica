<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\TicketCreated;
use App\Mail\TicketClosed;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    public function index()
    {
        return view('tickets.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high',
            'attachments.*' => 'nullable|file|mimes:jpeg,png,pdf|max:2048',
        ]);

        $closeToken = Str::random(60);

        $ticket = Ticket::create([
            'user_id' => auth()->id(),
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'status' => 'in_progress',
            'close_token' => $closeToken,
            'close_token_expires_at' => now()->addDays(30),
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('tickets', 'public');
                $ticket->attachments()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                ]);
            }
        }

        try {
            Mail::to('technologica.dev1@gmail.com')->send(new TicketCreated($ticket));
            $message = 'Ticket soumis avec succès ! Un email a été envoyé au responsable.';
        } catch (\Exception $e) {
            Log::error('Erreur envoi email ticket créé: ' . $e->getMessage());
            $message = 'Ticket créé avec succès, mais l\'email n\'a pas pu être envoyé au responsable.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'ticket_id' => $ticket->id,
        ]);
    }

 public function list(Request $request)
{
    try {
        $perPage = $request->get('per_page', 9);
        $page = $request->get('page', 1);
        $status = $request->get('status');
        $priority = $request->get('priority');
        $search = $request->get('search');

        Log::info('Search parameters:', [
            'search' => $search,
            'status' => $status,
            'priority' => $priority,
            'per_page' => $perPage,
            'page' => $page,
            'user_id' => auth()->id(),
        ]);

        $query = Ticket::with(['user.salarie'])
            ->where('user_id', auth()->id());

        // Correction 1: Vérifier que le statut n'est pas vide ET différent de 'all'
        if (!empty($status) && $status !== 'all') {
            $query->where('status', $status);
        }

        // Correction 2: Vérifier que la priorité n'est pas vide ET différente de 'all'
        if (!empty($priority) && $priority !== 'all') {
            $query->where('priority', $priority);
        }

        // Correction 3: Améliorer la recherche - rechercher dans sujet ET description
if (!empty($search)) {
        $searchTerm = '%' . trim($search) . '%';
        $query->where(function($q) use ($searchTerm) {
            $q->whereRaw('LOWER(subject) LIKE ?', [strtolower($searchTerm)])
              ->orWhereRaw('LOWER(description) LIKE ?', [strtolower($searchTerm)]);
        });
    }
          $tickets = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        Log::info('Tickets found:', [
            'count' => $tickets->count(),
            'total' => $tickets->total(),
            'search_term' => $search,
            'current_page' => $tickets->currentPage(),
            'last_page' => $tickets->lastPage()
        ]);

        $transformedTickets = $tickets->getCollection()->map(function ($ticket) {
            $priorityLabels = [
                'low' => 'Faible',
                'medium' => 'Moyenne',
                'high' => 'Élevée'
            ];

            $statusLabels = [
                'open' => 'Ouvert',
                'in_progress' => 'En attente',
                'closed' => 'Terminé'
            ];

            return [
                'id' => $ticket->id,
                'subject' => $ticket->subject ?? 'Sans sujet',
                'description' => $ticket->description ?? 'Sans description',
                'priority' => [
                    'value' => $ticket->priority,
                    'label' => $priorityLabels[$ticket->priority] ?? ucfirst($ticket->priority)
                ],
                'status' => $ticket->status,
                'status_label' => $statusLabels[$ticket->status] ?? ucfirst(str_replace('_', ' ', $ticket->status)),
                'category' => $ticket->category,
                'user' => [
                    'id' => $ticket->user ? $ticket->user->id : null,
                    'nom' => $ticket->user && $ticket->user->salarie ? $ticket->user->salarie->nom : 'Inconnu',
                    'prenom' => $ticket->user && $ticket->user->salarie ? $ticket->user->salarie->prenom : '',
                    'email' => $ticket->user ? $ticket->user->email : 'N/A'
                ],
                'created_at' => $ticket->created_at->format('d/m/Y H:i'),
                'updated_at' => $ticket->updated_at->format('d/m/Y H:i')
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedTickets,
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
                'from' => $tickets->firstItem(),
                'to' => $tickets->lastItem(),
                'has_more_pages' => $tickets->hasMorePages(),
                'prev_page_url' => $tickets->previousPageUrl(),
                'next_page_url' => $tickets->nextPageUrl(),
            ]
        ], 200);
    } catch (\Exception $e) {
        Log::error('Erreur lors de la récupération des tickets', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'params' => $request->all(),
        ]);
        return response()->json([
            'success' => false,
            'error' => 'Erreur lors de la récupération des tickets',
            'message' => $e->getMessage()
        ], 500);
    }
}

    public function close($id, $token, Request $request)
    {
        try {
            $ticket = Ticket::findOrFail($id);

            if ($ticket->close_token !== $token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lien de clôture invalide.'
                ], 403);
            }

            if ($ticket->close_token_expires_at && $ticket->close_token_expires_at < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce lien de clôture a expiré.'
                ], 400);
            }

            if ($ticket->status === 'closed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce ticket est déjà terminé.'
                ], 400);
            }

            $ticket->update([
                'status' => 'closed',
                'closed_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Le ticket a été marqué comme terminé avec succès.'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la clôture du ticket via lien: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue. Veuillez réessayer.'
            ], 500);
        }
    }

  public function closeTicket($id)
{
    try {
        $ticket = Ticket::findOrFail($id);
        Log::info('Attempting to close ticket', [
            'ticket_id' => $id,
            'ticket_user_id' => $ticket->user_id,
            'ticket_user_id_type' => gettype($ticket->user_id),
            'auth_user_id' => auth()->id(),
            'auth_user_id_type' => gettype(auth()->id()),
        ]);

        if ((string) $ticket->user_id != (string) auth()->id()) {
            Log::warning('Unauthorized closure attempt', [
                'ticket_id' => $id,
                'ticket_user_id' => $ticket->user_id,
                'auth_user_id' => auth()->id(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à clôturer ce ticket.'
            ], 403);
        }

        if ($ticket->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'Ce ticket est déjà terminé.'
            ], 400);
        }

        $ticket->update([
            'status' => 'closed',
            'closed_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket marqué comme terminé avec succès.'
        ]);
    } catch (\Exception $e) {
        Log::error('Erreur lors de la clôture du ticket: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la clôture du ticket.'
        ], 500);
    }
}
     public function destroy($id)
    {
        try {
            $ticket = Ticket::findOrFail($id);
            Log::info('Attempting to delete ticket', [
                'ticket_id' => $id,
                'ticket_user_id' => $ticket->user_id,
                'ticket_user_id_type' => gettype($ticket->user_id),
                'auth_user_id' => auth()->id(),
                'auth_user_id_type' => gettype(auth()->id()),
            ]);

            if ((string) $ticket->user_id != (string) auth()->id()) {
                Log::warning('Unauthorized deletion attempt', [
                    'ticket_id' => $id,
                    'ticket_user_id' => $ticket->user_id,
                    'auth_user_id' => auth()->id(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à supprimer ce ticket.'
                ], 403);
            }

            $ticket->attachments()->delete();
            $ticket->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ticket supprimé avec succès.'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du ticket: ' . $e->getMessage(), [
                'ticket_id' => $id,
                'auth_user_id' => auth()->id(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du ticket.'
            ], 500);
        }
    }
        public function show($id)
{
    try {
        $ticket = Ticket::with(['user.salarie', 'attachments'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        $priorityLabels = [
            'low' => 'Faible',
            'medium' => 'Moyenne',
            'high' => 'Élevée'
        ];

        $statusLabels = [
            'open' => 'Ouvert',
            'in_progress' => 'En attente',
            'closed' => 'Terminé'
        ];

        $transformedTicket = [
            'id' => $ticket->id,
            'subject' => $ticket->subject ?? 'Sans sujet',
            'description' => $ticket->description ?? 'Sans description',
            'priority' => [
                'value' => $ticket->priority,
                'label' => $priorityLabels[$ticket->priority] ?? ucfirst($ticket->priority)
            ],
            'status' => $ticket->status,
            'status_label' => $statusLabels[$ticket->status] ?? ucfirst(str_replace('_', ' ', $ticket->status)),
            'category' => $ticket->category,
            'user' => [
                'id' => $ticket->user ? $ticket->user->id : null,
                'nom' => $ticket->user && $ticket->user->salarie ? $ticket->user->salarie->nom : 'Inconnu',
                'prenom' => $ticket->user && $ticket->user->salarie ? $ticket->user->salarie->prenom : '',
                'email' => $ticket->user ? $ticket->user->email : 'N/A'
            ],
            'created_at' => $ticket->created_at->format('d/m/Y H:i'),
            'updated_at' => $ticket->updated_at->format('d/m/Y H:i'),
            'attachments' => $ticket->attachments->map(function ($attachment) {
                return [
                    'id' => $attachment->id,
                    'file_name' => $attachment->file_name,
                    'file_path' => asset('storage/' . $attachment->file_path),
                    'file_type' => $attachment->file_type,
                    'is_image' => in_array($attachment->file_type, ['image/jpeg', 'image/png']),
                    'is_pdf' => $attachment->file_type === 'application/pdf',
                ];
            })->toArray()
        ];

        return response()->json([
            'success' => true,
            'data' => $transformedTicket
        ], 200);
    } catch (\Exception $e) {
        Log::error('Erreur lors de la récupération des détails du ticket: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error' => 'Erreur lors de la récupération des détails du ticket',
            'message' => $e->getMessage()
        ], 500);
    }
}
}