@extends('layouts.app')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-4">
                <h1 class="h3 mb-2"><i class="bi bi-shield-check me-2"></i>Consentimiento Legal</h1>
                <p class="text-muted mb-4">Para continuar usando la plataforma, debes aceptar los documentos legales vigentes (versión {{ $currentVersion }}).</p>

                <form method="POST" action="{{ route('legal.accept') }}">
                    @csrf

                    @if($needsPrivacy)
                    <div class="form-check mb-3 p-3 border rounded d-flex align-items-start gap-2">
                        <input class="form-check-input mt-1" type="checkbox" name="accept_privacy" id="acceptPrivacy" value="1" required>
                        <label class="form-check-label" for="acceptPrivacy">
                            He leído y acepto la <a href="{{ route('legal.privacy') }}" target="_blank">Política de Privacidad</a>.
                        </label>
                    </div>
                    @endif

                    @if($needsTerms)
                    <div class="form-check mb-3 p-3 border rounded d-flex align-items-start gap-2">
                        <input class="form-check-input mt-1" type="checkbox" name="accept_terms" id="acceptTerms" value="1" required>
                        <label class="form-check-label" for="acceptTerms">
                            He leído y acepto los <a href="{{ route('legal.terms') }}" target="_blank">Términos y Condiciones de Uso</a>.
                        </label>
                    </div>
                    @endif

                    @error('accept_privacy')
                        <div class="alert alert-danger py-2">{{ $message }}</div>
                    @enderror
                    @error('accept_terms')
                        <div class="alert alert-danger py-2">{{ $message }}</div>
                    @enderror

                    <button class="btn btn-primary btn-action" type="submit"><i class="bi bi-check-lg me-1"></i>Aceptar y continuar</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection