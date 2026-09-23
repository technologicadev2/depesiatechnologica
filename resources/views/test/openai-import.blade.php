@extends('master_page.app')

@section('title')
    Test OpenAI Vision - Import Fiche
@endsection

<style>
    .test-container {
        max-width: 1000px;
        margin: 30px auto;
        padding: 20px;
    }
    .upload-card {
        background: #f8f9fa;
        border: 2px solid #dee2e6;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .preview-box {
        margin-top: 20px;
        text-align: center;
    }
    .preview-box img, .preview-box embed {
        max-width: 100%;
        max-height: 60vh;
        border: 1px solid #ccc;
        border-radius: 8px;
    }
    .result-box {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-top: 20px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.06);
    }
</style>

@section('content')
<div class="test-container">
    <h2 class="text-center text-primary mb-4">
        <i class="fas fa-robot me-2"></i>Test Import Fiche avec OpenAI GPT-4o Vision
    </h2>

    <div class="upload-card">
        <form action="{{ route('test.openai.import') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Fichier (JPG, PNG ou PDF)</label>
                    <input type="file" name="file" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required onchange="previewFile()">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Année</label>
                    <input type="number" name="annee" class="form-control" value="{{ date('Y') }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Mois</label>
                    <select name="mois" class="form-control" required>
                        @for($m=1; $m<=12; $m++)
                            <option value="{{ $m }}">{{ $m }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type de fiche</label>
                    <select name="type_image" class="form-control" required>
                        <option value="staff_work_sheet">Staff Work Sheet</option>
                        <option value="other_type1">Type 1</option>
                        <option value="other_type2">Type 2</option>
                        <option value="other_type3">Type 3</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-upload"></i> Tester
                    </button>
                </div>
            </div>
        </form>

        <div class="preview-box mt-4" id="previewBox">
            <p class="text-muted">Prévisualisation du fichier</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success result-box">
            <h5>{{ session('success') }}</h5>
        </div>
        <div class="result-box">
            <h5 class="text-primary">JSON extrait :</h5>
            <pre class="bg-light p-3 rounded">{{ json_encode(session('data'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
        <div class="result-box">
            <h5 class="text-info">Réponse brute OpenAI :</h5>
            <pre class="bg-light p-3 rounded small">{{ session('raw') }}</pre>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger result-box">
            <strong>Erreur :</strong> {{ session('error') }}
            @if(session('debug'))
                <hr>
                <pre class="bg-light p-3 rounded small">{{ session('debug') }}</pre>
            @endif
        </div>
    @endif
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:rgba(0,0,0,0.8);border:none;">
            <div class="modal-body text-center text-white">
                <div class="spinner-border" style="width:4rem;height:4rem;"></div>
                <h4 class="mt-3">Analyse en cours avec GPT-4o...</h4>
                <p>Patientez quelques secondes</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function previewFile() {
    const input = document.querySelector('input[type="file"]');
    const preview = document.getElementById('previewBox');
    const file = input.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        if (file.type === 'application/pdf') {
            preview.innerHTML = `<embed src="${e.target.result}" type="application/pdf" width="100%" height="600px">`;
        } else {
            preview.innerHTML = `<img src="${e.target.result}" alt="Prévisualisation">`;
        }
    };
    reader.readAsDataURL(file);
}

document.getElementById('uploadForm').addEventListener('submit', function() {
    $('#loadingModal').modal('show');
});
</script>
@endsection