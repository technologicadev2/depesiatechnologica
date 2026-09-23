@extends('master_page.app')
@section('title')
    Mes demandes
@endsection

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Mes demandes</h4>
        <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createTicketModal">
            <i class="bx bx-plus me-2"></i>Ouvrir un nouveau ticket
        </button>
    </div>

    <div class="row mb-4">

    <div class="col-md-3">
        <div class="input-group">
            <input type="text" id="searchInput" class="form-control" placeholder="Rechercher un ticket..." value="{{ request()->query('search') }}">
            {{-- <button class="btn btn-outline-secondary" type="button" id="clearSearch"> --}}
                {{-- <i class="bx bx-x"></i> --}}
            </button>
        </div>
    </div>
        <div class="col-md-3">
            <select id="statusFilter" class="form-select">
                <option value="all">Tous les statuts</option>
                <option value="open">Ouvert</option>
                <option value="in_progress">En attente</option>
                <option value="closed">Terminé</option>
            </select>
        </div>
        <div class="col-md-3">
            <select id="priorityFilter" class="form-select">
                <option value="all">Toutes les priorités</option>
                <option value="low">Faible</option>
                <option value="medium">Moyenne</option>
                <option value="high">Élevée</option>
            </select>
        </div>
        <div class="col-md-2">
            <select id="perPageSelect" class="form-select">
                <option value="6">6 par page</option>
                <option value="9" selected>9 par page</option>
                <option value="12">12 par page</option>
                <option value="18">18 par page</option>
            </select>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="text-muted small" id="paginationInfo"></div>
        <div class="text-muted small" id="totalTickets"></div>
    </div>

    <div class="row" id="ticketsContainer"></div>

    <div class="d-flex justify-content-center mt-4">
        <nav aria-label="Pagination des tickets">
            <ul class="pagination pagination-lg" id="paginationNav"></ul>
        </nav>
    </div>
</div>

<!-- Modals (unchanged) -->
<div class="modal fade" id="createTicketModal" tabindex="-1" aria-labelledby="createTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
              <div class="modal-header bg-light text-dark">
                <h5 class="modal-title" id="createTicketModalLabel">
                    <i class="bx bx-ticket me-2"></i>Créer un nouveau ticket
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createTicketForm" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="subject" class="form-label">Sujet</label>
                        <input type="text" name="subject" id="subject" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="priority" class="form-label">Priorité</label>
                        <select name="priority" id="priority" class="form-control" required>
                            <option value="low">Faible</option>
                            <option value="medium">Moyenne</option>
                            <option value="high">Élevée</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="attachments" class="form-label">Joindre des fichiers (images ou documents)</label>
                        <input type="file" name="attachments[]" id="attachments" class="form-control" multiple accept="image/jpeg,image/png,application/pdf">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-send me-2"></i>Soumettre le ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
             <div class="modal-header bg-light text-dark">
                <h5 class="modal-title" id="deleteModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer ce ticket ? Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Supprimer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel">Succès</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="successModalMessage">Le ticket a été marqué comme terminé avec succès.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="errorModalLabel">Erreur</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="errorModalMessage">Une erreur est survenue.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ticketDetailsModal" tabindex="-1" aria-labelledby="ticketDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-light text-dark">
                <h5 class="modal-title" id="ticketDetailsModalLabel">Détails du ticket</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="ticketDetailsContent">
                    <h6 class="fw-bold" id="ticketSubject"></h6>
                    <p class="text-muted" id="ticketDescription"></p>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Statut :</strong> <span id="ticketStatus"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Priorité :</strong> <span id="ticketPriority"></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Créé par :</strong> <span id="ticketUser"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Date de création :</strong> <span id="ticketCreatedAt"></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <strong>Pièces jointes :</strong>
                        <div id="ticketAttachments" class="mt-2"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<style>
.ticket-card {
    transition: all 0.3s ease;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
}
.ticket-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.priority-badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
}
.status-badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    border: none;
}
.ticket-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #e0e0e0;
}
.ticket-actions {
    background: #fafafa;
    border-top: 1px solid #e0e0e0;
}
.btn-action {
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    padding: 0.4rem 1rem;
}
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6c757d;
}
.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}
.pagination-lg .page-item .page-link {
    border-radius: 8px;
    margin: 0 2px;
    font-weight: 500;
}
.pagination-lg .page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    border-radius: 12px;
}
</style>

<script>
$(document).ready(function () {
    const successSound = new Audio('/assets/audio/success.mp3');
    const errorSound = new Audio('/assets/audio/error.mp3');

    let currentPage = 1;
    let currentFilters = {
        search: '',
        status: 'all',
        priority: 'all',
        per_page: 9
    };

    console.log('Initializing ticket list with filters:', currentFilters);

    loadTickets();
const debouncedSearch = debounce(function(searchTerm) {
        currentFilters.search = searchTerm;
        currentPage = 1;
        console.log('Search term updated:', searchTerm);
        loadTickets();
    }, 500);

    // Search input handler
    $('#searchInput').on('input', function() {
        const searchTerm = $(this).val().trim();
        debouncedSearch(searchTerm);
    });

    // Clear search button
    $('#clearSearch').on('click', function() {
        console.log('Clearing search input');
        $('#searchInput').val('');
        currentFilters.search = '';
        currentPage = 1;
        loadTickets();
    });

    $('#statusFilter').on('change', function() {
        currentFilters.status = $(this).val();
        currentPage = 1;
        console.log('Status filter changed:', currentFilters.status);
        loadTickets();
    });

    $('#priorityFilter').on('change', function() {
        currentFilters.priority = $(this).val();
        currentPage = 1;
        console.log('Priority filter changed:', currentFilters.priority);
        loadTickets();
    });

    $('#perPageSelect').on('change', function() {
        currentFilters.per_page = $(this).val();
        currentPage = 1;
        console.log('Per page changed:', currentFilters.per_page);
        loadTickets();
    });

    $('#createTicketForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        console.log('Submitting create ticket form');

        $.ajax({
            url: "{{ route('tickets.store') }}",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#createTicketForm button[type="submit"]').prop('disabled', true)
                    .html('<i class="bx bx-loader-alt bx-spin me-2"></i>Envoi en cours...');
            },
            success: function(response) {
                console.log('Ticket created successfully:', response);
                $('#createTicketModal').modal('hide');
                $('#createTicketForm')[0].reset();
                currentPage = 1;
                loadTickets();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Succès !',
                    text: response.message,
                    timer: 3000,
                    showConfirmButton: false
                });
                
                try { successSound.play(); } catch(e) {}
            },
            error: function(xhr) {
                console.error('Error creating ticket:', xhr.responseJSON || xhr);
                let message = 'Une erreur est survenue lors de la création du ticket.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: message,
                    timer: 5000
                });
                
                try { errorSound.play(); } catch(e) {}
            },
            complete: function() {
                $('#createTicketForm button[type="submit"]').prop('disabled', false)
                    .html('<i class="bx bx-send me-2"></i>Soumettre le ticket');
            }
        });
    });

function loadTickets() {
    showLoadingOverlay();
    
    const params = {
        page: currentPage,
        per_page: currentFilters.per_page,
        search: currentFilters.search,
        status: currentFilters.status === 'all' ? '' : currentFilters.status,
        priority: currentFilters.priority === 'all' ? '' : currentFilters.priority
    };

    $.ajax({
        url: "{{ route('tickets.list') }}",
        type: 'GET',
        data: params,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success && response.data && response.pagination) {
                displayTickets(response.data);
                updatePaginationInfo(response.pagination);
                generatePagination(response.pagination);
            } else {
                console.error('Invalid response structure:', response);
                showError('Réponse invalide du serveur.');
            }
        },
        error: function(xhr) {
            console.error('AJAX Error:', xhr);
            let errorMessage = xhr.responseJSON?.message || 'Impossible de charger les tickets.';
            showError(errorMessage);
        },
        complete: function() {
            hideLoadingOverlay();
        }
    });
}
  
function displayTickets(tickets) {
        console.log('Displaying tickets:', tickets);
        if (!Array.isArray(tickets) || tickets.length === 0) {
            $('#ticketsContainer').html(`
                <div class="col-12">
                    <div class="empty-state">
                        <i class="bx bx-ticket"></i>
                        <h5>Aucun ticket trouvé</h5>
                        <p>Aucun ticket ne correspond à votre recherche par sujet. Essayez un autre terme.</p>
                    </div>
                </div>
            `);
            return;
        }

        let html = '';
        tickets.forEach(function(ticket) {
            const priorityClass = {
                'low': 'success',
                'medium': 'warning',
                'high': 'danger'
            }[ticket.priority?.value?.toLowerCase()] || 'secondary';

            const statusClass = {
                'closed': 'success',
                'in_progress': 'warning',
                'open': 'info'
            }[ticket.status] || 'secondary';

            const priorityIcon = {
                'low': '🟢',
                'medium': '🟡',
                'high': '🔴'
            }[ticket.priority?.value?.toLowerCase()] || '⚪';

            const statusText = {
                'closed': 'Terminé',
                'in_progress': 'En attente',
                'open': 'Ouvert'
            }[ticket.status] || ticket.status_label || 'Inconnu';

            html += `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card ticket-card h-100">
                        <div class="ticket-header p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title mb-0 fw-bold">${ticket.subject || 'Sans sujet'}</h6>
                                <div class="d-flex gap-2">
                                    <span class="badge bg-${priorityClass} priority-badge">
                                        ${priorityIcon} ${ticket.priority?.label || 'Inconnu'}
                                    </span>
                                </div>
                            </div>
                            <p class="card-text text-muted small mb-2">
                                ${ticket.description?.length > 100 ? ticket.description.substring(0, 100) + '...' : ticket.description || 'Sans description'}
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="bx bx-user me-1"></i>${ticket.user?.prenom || ''} ${ticket.user?.nom || 'Inconnu'}
                                </small>
                                <span class="status-badge bg-${statusClass}">
                                    ${statusText}
                                </span>
                            </div>
                        </div>
                        ${ticket.status !== 'closed' ? `
                        <div class="ticket-actions p-3">
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary btn-sm btn-action flex-fill view-ticket" data-id="${ticket.id}">
                                    <i class="bx bx-show me-1"></i>Voir détails
                                </button>
                                <button class="btn btn-success btn-sm btn-action close-ticket" data-id="${ticket.id}">
                                    <i class="bx bx-check me-1"></i>Terminer
                                </button>
                                <button class="btn btn-danger btn-sm btn-action delete-ticket" data-id="${ticket.id}" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                    <i class="bx bx-trash me-1"></i>Supprimer
                                </button>
                            </div>
                        </div>
                        ` : `
                        <div class="ticket-actions p-3">
                            <button class="btn btn-outline-secondary btn-sm btn-action w-100" disabled>
                                <i class="bx bx-check-circle me-1"></i>Ticket terminé
                            </button>
                        </div>
                        `}
                    </div>
                </div>
            `;
        });

        $('#ticketsContainer').html(html);
    }

    function updatePaginationInfo(pagination) {
        console.log('Updating pagination:', pagination);
        const from = pagination.from || 0;
        const to = pagination.to || 0;
        const total = pagination.total || 0;
        
        $('#paginationInfo').html(`
            Affichage de ${from} à ${to} sur ${total} ticket${total > 1 ? 's' : ''}
        `);
        
        $('#totalTickets').html(`
            Total: ${total} ticket${total > 1 ? 's' : ''}
        `);
    }

    function generatePagination(pagination) {
        console.log('Generating pagination:', pagination);
        let html = '';
        
        if (pagination.current_page > 1) {
            html += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.current_page - 1}">
                        <i class="bx bx-chevron-left"></i> Précédent
                    </a>
                </li>
            `;
        } else {
            html += `
                <li class="page-item disabled">
                    <span class="page-link">
                        <i class="bx bx-chevron-left"></i> Précédent
                    </span>
                </li>
            `;
        }

        const totalPages = pagination.last_page || 1;
        const currentPage = pagination.current_page || 1;
        
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, currentPage + 2);
        
        if (endPage - startPage < 4) {
            if (startPage === 1) {
                endPage = Math.min(totalPages, startPage + 4);
            } else if (endPage === totalPages) {
                startPage = Math.max(1, endPage - 4);
            }
        }

        if (startPage > 1) {
            html += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="1">1</a>
                </li>
            `;
            if (startPage > 2) {
                html += `
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                `;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            if (i === currentPage) {
                html += `
                    <li class="page-item active">
                        <span class="page-link">${i}</span>
                    </li>
                `;
            } else {
                html += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            }
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += `
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                `;
            }
            html += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>
                </li>
            `;
        }

        if (pagination.current_page < pagination.last_page) {
            html += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.current_page + 1}">
                        Suivant <i class="bx bx-chevron-right"></i>
                    </a>
                </li>
            `;
        } else {
            html += `
                <li class="page-item disabled">
                    <span class="page-link">
                        Suivant <i class="bx bx-chevron-right"></i>
                    </span>
                </li>
            `;
        }

        $('#paginationNav').html(html);
        
        if (pagination.last_page <= 1) {
            $('#paginationNav').parent().hide();
        } else {
            $('#paginationNav').parent().show();
        }
    }

    $(document).on('click', '.page-link[data-page]', function(e) {
        e.preventDefault();
        currentPage = parseInt($(this).data('page'));
        console.log('Navigating to page:', currentPage);
        loadTickets();
        
        $('html, body').animate({
            scrollTop: $('#ticketsContainer').offset().top - 100
        }, 300);
    });

    function showLoadingOverlay() {
        $('#ticketsContainer').css('position', 'relative').append(`
            <div class="loading-overlay">
                <div class="text-center">
                    <div class="spinner-border text-primary mb-2" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <div class="text-muted">Chargement des tickets...</div>
                </div>
            </div>
        `);
    }

    function hideLoadingOverlay() {
        $('.loading-overlay').remove();
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

 $(document).on('click', '.close-ticket', function(e) {
    e.preventDefault();
    const ticketId = $(this).data('id');
    console.log('Closing ticket:', ticketId);
    
    Swal.fire({
        title: 'Confirmer la clôture',
        text: 'Voulez-vous marquer ce ticket comme terminé ?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Oui, terminer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/tickets/${ticketId}/close-ticket`, // Updated route
                type: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    console.log('Ticket closed:', response);
                    loadTickets();
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès !',
                        text: response.message,
                        timer: 3000,
                        showConfirmButton: false
                    });
                    try { successSound.play(); } catch(e) {}
                },
                error: function(xhr) {
                    console.error('Error closing ticket:', xhr.responseJSON || xhr);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: xhr.responseJSON?.message || 'Erreur lors de la clôture du ticket.',
                        timer: 5000
                    });
                    try { errorSound.play(); } catch(e) {}
                }
            });
        }
    });
});

    $(document).on('click', '.delete-ticket', function() {
        const ticketId = $(this).data('id');
        console.log('Preparing to delete ticket:', ticketId);
        $('#confirmDelete').data('ticket-id', ticketId);
    });

    $('#confirmDelete').on('click', function() {
        const ticketId = $(this).data('ticket-id');
        console.log('Deleting ticket:', ticketId);
        $.ajax({
            url: `/tickets/${ticketId}`,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log('Ticket deleted:', response);
                $('#deleteModal').modal('hide');
                loadTickets();
                Swal.fire({
                    icon: 'success',
                    title: 'Succès !',
                    text: response.message,
                    timer: 3000,
                    showConfirmButton: false
                });
                try { successSound.play(); } catch(e) {}
            },
            error: function(xhr) {
                console.error('Error deleting ticket:', xhr.responseJSON || xhr);
                $('#deleteModal').modal('hide');
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: xhr.responseJSON?.message || 'Erreur lors de la suppression du ticket.',
                    timer: 5000
                });
                try { errorSound.play(); } catch(e) {}
            }
        });
    });

    $(document).on('click', '.view-ticket', function(e) {
        e.preventDefault();
        const ticketId = $(this).data('id');
        console.log('Viewing ticket details:', ticketId);
        // TODO: Implement ticket details view
    });

    $('#createTicketModal').on('hidden.bs.modal', function () {
        $('#createTicketForm')[0].reset();
        $('#createTicketForm .is-invalid').removeClass('is-invalid');
        $('#createTicketForm .invalid-feedback').remove();
    });
});
$(document).on('click', '.view-ticket', function(e) {
    e.preventDefault();
    const ticketId = $(this).data('id');
    console.log('Fetching details for ticket:', ticketId);

    $.ajax({
        url: `/tickets/${ticketId}`,
        yüzy: 'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success && response.data) {
                console.log('Ticket details received:', response.data);
                displayTicketDetails(response.data);
                $('#ticketDetailsModal').modal('show');
            } else {
                console.error('Invalid response structure:', response);
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: 'Réponse invalide du serveur.',
                    timer: 5000
                });
                try { errorSound.play(); } catch(e) {}
            }
        },
        error: function(xhr) {
            console.error('Error fetching ticket details:', xhr.responseJSON || xhr);
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: xhr.responseJSON?.message || 'Erreur lors de la récupération des détails du ticket.',
                timer: 5000
            });
            try { errorSound.play(); } catch(e) {}
        }
    });
});

function displayTicketDetails(ticket) {
    $('#ticketSubject').text(ticket.subject || 'Sans sujet');
    $('#ticketDescription').text(ticket.description || 'Sans description');
    $('#ticketStatus').text(ticket.status_label || 'Inconnu');
    $('#ticketPriority').text(ticket.priority?.label || 'Inconnu');
    $('#ticketUser').text(`${ticket.user?.prenom || ''} ${ticket.user?.nom || 'Inconnu'}`);
    $('#ticketCreatedAt').text(ticket.created_at || 'N/A');

    // Afficher les pièces jointes
    let attachmentsHtml = '';
    if (ticket.attachments && ticket.attachments.length > 0) {
        ticket.attachments.forEach(function(attachment) {
            if (attachment.is_image) {
                attachmentsHtml += `
                    <div class="mb-2">
                        <a href="${attachment.file_path}" target="_blank">
                            <img src="${attachment.file_path}" alt="${attachment.file_name}" class="img-fluid" style="max-width: 200px; max-height: 200px;">
                        </a>
                        <p class="small mb-0">${attachment.file_name}</p>
                    </div>
                `;
            } else if (attachment.is_pdf) {
                attachmentsHtml += `
                    <div class="mb-2">
                        <a href="${attachment.file_path}" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="bx bx-file me-1"></i> ${attachment.file_name}
                        </a>
                    </div>
                `;
            } else {
                attachmentsHtml += `
                    <div class="mb-2">
                        <a href="${attachment.file_path}" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="bx bx-file me-1"></i> ${attachment.file_name}
                        </a>
                    </div>
                `;
            }
        });
    } else {
        attachmentsHtml = '<p class="text-muted">Aucune pièce jointe.</p>';
    }
    $('#ticketAttachments').html(attachmentsHtml);
}

</script>
@endsection