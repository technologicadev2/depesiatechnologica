@extends('master_page.app')

@section('title')
    Profile
@endsection

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- SweetAlert2 Notifications -->
    @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Succès',
                    text: "{{ session('success') }}",
                    position: 'center',
                    showConfirmButton: true,
                    confirmButtonText: 'OK',
                    timer: 5000,
                    timerProgressBar: true,
                    didOpen: () => {
                        const successSound = new Audio('{{ asset('assets/audio/success.mp3') }}');
                        successSound.play().catch(error => console.log('Erreur de lecture audio:', error));
                    }
                });
            });
        </script>
    @endif
    @if (session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: "{{ session('error') }}",
                    position: 'center',
                    showConfirmButton: true,
                    confirmButtonText: 'OK',
                    timer: 5000,
                    timerProgressBar: true,
                    didOpen: () => {
                        const errorSound = new Audio('{{ asset('assets/audio/error.mp3') }}');
                        errorSound.play().catch(error => console.log('Erreur de lecture audio:', error));
                    }
                });
            });
        </script>
    @endif

    <style>
        .error {
            color: red;
            font-size: 0.875em;
        }

        .custom-btn {
            background-color: #007bff;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
        }

        .custom-btn:hover {
            background-color: #0056b3;
        }

        .profile-section {
            display: flex;
            align-items: center;
        }

        .form-group {
            margin-bottom: 0 !important;
        }

        .btn-align {
            margin-top: 1.5rem;
            /* Align button vertically with input */
        }
    </style>

    <div class="container mt-5">
        <!-- Profile Update Section -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="tab-pane active" id="account-general">
                    <!-- Profile Form -->
                    <form id="profile-form" action="{{ route('profil.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="file" id="profil_file" name="profil_file" hidden
                            accept="image/png,image/jpg,image/jpeg">
                        <div class="row align-items-center">
                            <!-- Profile Image -->
                            <div class="col-12 col-sm-4 col-md-3">
                                <div class="media">
                                    <a href="javascript:void(0);" class="me-3">
                                        @if (auth()->user()->profil_image)
                                            <img id="profile-image"
                                                src="{{ asset('storage/uploads/' . auth()->user()->profil_image) }}?v={{ time() }}"
                                                class="rounded" alt="Profile Image" height="80" width="80">
                                        @else
                                            <img id="profile-image"
                                                src="{{ asset('storage/uploads/default_profil.png') }}?v={{ time() }}"
                                                class="rounded" alt="Profile Image" height="80" width="80">
                                        @endif
                                    </a>
                                    <div class="media-body">
                                        @if (auth()->user()->profil_image && auth()->user()->profil_image !== '')
                                            <form action="{{ route('profil.image.delete') }}" method="POST"
                                                id="delete-image-form">
                                                @csrf
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-secondary mb-2 delete-image-btn">Supprimer</button>
                                            </form>
                                        @endif
                                        <label for="profil_file" class="btn btn-sm btn-primary mb-2 me-2">Modifier
                                            l'image</label>
                                        <p>JPG, JPEG ou PNG autorisé.</p>
                                    </div>
                                </div>
                            </div>
                            <!-- Username Input -->
                            <div class="col-12 col-sm-4 col-md-3">
                                <div class="form-group">
                                    <label>Nom d'utilisateur</label>
                                    <input type="text" class="form-control @error('username') is-invalid @enderror"
                                        name="username" value="{{ old('username', auth()->user()->username ?? '') }}"
                                        placeholder="Saisir le nom d'utilisateur">
                                    @error('username')
                                        <span class="error">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <!-- Submit Button -->
                            <div class="col-12 col-sm-4 col-md-3 btn-align">
                                <button type="submit" class="custom-btn">Modifier</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Password Update Section -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-bold mb-0">MODIFIER MOT DE PASSE</h3>
        </div>
        <div class="card">
            <div class="card-body">
                <form action="{{ route('profil.password') }}" method="POST" autocomplete="off" id="password-form">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 col-12">
                            <div class="form-group mb-3">
                                <label>Mot de passe actuel</label>
                                <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                                    name="current_password" placeholder="Saisir le mot de passe actuel">
                                @error('current_password')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="form-group mb-3">
                                <label>Nouveau mot de passe</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                    name="password" placeholder="Saisir le nouveau mot de passe">
                                @error('password')
                                    <span class="error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                            <div class="form-group mb-3">
                                <label>Confirmez le mot de passe</label>
                                <input type="password" class="form-control" name="password_confirmation"
                                    placeholder="Confirmez le mot de passe">
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="custom-btn">Modifier</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Auto-submit profile form when image is selected
        document.getElementById('profil_file').addEventListener('change', function() {
            document.getElementById('profile-form').submit();
        });

        // Confirm deletion of profile image
        document.querySelectorAll('.delete-image-btn').forEach(button => {
            button.addEventListener('click', function() {
                Swal.fire({
                    icon: 'warning',
                    title: 'Confirmer la suppression',
                    text: 'Êtes-vous sûr de vouloir supprimer votre image de profil ?',
                    showCancelButton: true,
                    confirmButtonText: 'Oui, supprimer',
                    cancelButtonText: 'Annuler',
                    position: 'center',
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.closest('form').submit();
                    }
                });
            });
        });

        document.getElementById('profile-form').addEventListener('submit', function(e) {
            if (!e.submitter || e.submitter.id !== 'profil_file') {
                e.preventDefault();
                Swal.fire({
                    icon: 'question',
                    title: 'Confirmer la modification',
                    text: 'Voulez-vous enregistrer les modifications de votre profil ?',
                    showCancelButton: true,
                    confirmButtonText: 'Oui, enregistrer',
                    cancelButtonText: 'Annuler',
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            }
        });

        document.getElementById('password-form').addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                icon: 'question',
                title: 'Confirmer la modification',
                text: 'Voulez-vous modifier votre mot de passe ?',
                showCancelButton: true,
                confirmButtonText: 'Oui, modifier',
                cancelButtonText: 'Annuler',
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });
    </script>
@endsection
