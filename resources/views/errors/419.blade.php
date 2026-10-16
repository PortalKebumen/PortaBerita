@extends('errors.layout')
@section('code', '419')
@section('title', 'Sesi telah berakhir')
@section('message')
    <p>Halaman terlalu lama dibiarkan terbuka sehingga sesi keamanannya kedaluwarsa.</p>
    <p>Muat ulang halaman, lalu kirim ulang formulir Anda.</p>
@endsection
@section('extra-actions')
    <a class="btn btn-secondary" href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}">Kembali &amp; Muat Ulang</a>
@endsection
