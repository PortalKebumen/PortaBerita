{{-- Cadangan untuk kode 5xx lain yang tidak punya halaman sendiri --}}
@extends('errors.layout')
@section('code', isset($exception) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : '500')
@section('title', 'Layanan sedang terganggu')
@section('message')
    <p>Maaf, layanan kami sedang mengalami gangguan sementara. Silakan coba lagi beberapa saat lagi.</p>
@endsection
