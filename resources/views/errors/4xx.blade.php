{{-- Cadangan untuk kode 4xx lain yang tidak punya halaman sendiri --}}
@extends('errors.layout')
@section('code', isset($exception) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : '400')
@section('title', 'Permintaan tidak dapat diproses')
@section('message')
    <p>Permintaan Anda tidak dapat kami proses. Periksa kembali alamat atau isian Anda, lalu coba lagi.</p>
@endsection
