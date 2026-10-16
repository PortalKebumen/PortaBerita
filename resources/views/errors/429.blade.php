{{-- Tetap ringan & mandiri (lihat layout) karena tampil justru saat ada lonjakan permintaan --}}
@extends('errors.layout')
@php
    $retry = (int) (($exception ?? null)?->getHeaders()['Retry-After'] ?? 60);
    $wait = $retry >= 120 ? ceil($retry / 60) . ' menit' : $retry . ' detik';
@endphp
@section('code', '429')
@section('title', 'Terlalu banyak permintaan')
@section('message')
    <p>Kami menerima terlalu banyak permintaan dari jaringan Anda dalam waktu singkat.</p>
    <p>Silakan tunggu sekitar <strong>{{ $wait }}</strong>, lalu coba lagi.</p>
@endsection
