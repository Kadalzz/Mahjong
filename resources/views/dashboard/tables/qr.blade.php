@extends('layouts.dashboard')
@section('title', 'QR Meja')
@section('page-title', 'QR Meja')

@section('content')
<div class="d-flex justify-content-end mb-3 d-print-none">
    <button type="button" class="btn btn-gold" onclick="window.print()"><i class="bi bi-printer me-1"></i>Cetak</button>
</div>

@unless($domain)
    <div class="alert alert-warning">POINTS_DOMAIN belum diisi di file env. QR belum bisa dibuat.</div>
@endunless

<div class="row g-3">
    @foreach($tables as $table)
    <div class="col-md-4">
        <div class="card-dark h-100">
            <div class="card-body text-center">
                <h5>{{ $table->displayName() }}</h5>
                @if($domain)
                    <div data-qr="{{ request()->getScheme() }}://{{ $domain }}/meja/{{ $table->qr_code }}"></div>
                @endif
                <div class="mt-2 fw-bold">{{ $table->qr_code }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@vite('resources/js/poin-qr.js')
@endsection
