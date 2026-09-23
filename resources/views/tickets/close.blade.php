<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clôture du ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .modal-content {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .modal-header-success {
            background: #28a745;
            color: white;
        }
        .modal-header-error {
            background: #dc3545;
            color: white;
        }
        .modal-body {
            padding: 20px;
            font-size: 16px;
        }
        .modal-footer {
            border-top: none;
            padding: 15px 20px;
        }
        .btn-primary {
            background: #007bff;
            border: none;
            border-radius: 6px;
            padding: 10px 20px;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal-header-success">
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

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal-header-error">
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            const closeUrl = "{{ route('tickets.close', ['id' => $id, 'token' => $token]) }}";
            console.log('Close URL:', closeUrl); // Pour déboguer

            $.ajax({
                url: closeUrl,
                type: 'POST', // Changé en POST pour éviter les problèmes de cache
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}' // Ajout du token CSRF
                },
                success: function(response) {
                    console.log('Réponse:', response); // Pour déboguer
                    if (response.success) {
                        $('#successModalMessage').text(response.message);
                        $('#successModal').modal('show');
                    } else {
                        $('#errorModalMessage').text(response.message);
                        $('#errorModal').modal('show');
                    }
                },
                error: function(xhr) {
                    console.error('Erreur AJAX:', xhr);
                    $('#errorModalMessage').text(xhr.responseJSON?.message || 'Une erreur est survenue lors de la clôture du ticket.');
                    $('#errorModal').modal('show');
                }
            });
        });
    </script>
</body>
</html>