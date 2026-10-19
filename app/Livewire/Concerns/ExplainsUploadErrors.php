<?php

namespace App\Livewire\Concerns;

use App\Support\UploadLimit;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Mengganti pesan bawaan Livewire "newFile gagal diunggah" dengan penjelasan penyebab dan langkah
 * yang bisa dilakukan pengguna. Dipakai bersama WithFileUploads:
 *
 *     use WithFileUploads, ExplainsUploadErrors {
 *         ExplainsUploadErrors::_uploadErrored insteadof WithFileUploads;
 *     }
 */
trait ExplainsUploadErrors
{
    /**
     * Dipanggil JS Livewire saat unggahan sementara gagal. $errorsInJson berisi error validasi bila
     * server menjawab 422; null bila koneksi putus, sesi/tanda tangan kedaluwarsa (401),
     * berkas melebihi post_max_size (413), atau error server (500).
     */
    public function _uploadErrored($name, $errorsInJson, $isMultiple)
    {
        $this->dispatch('upload:errored', name: $name)->self();

        $limit = UploadLimit::label();

        if ($errorsInJson !== null) {
            $errors = json_decode($errorsInJson, true)['errors'] ?? [];
            Log::warning('Unggahan berkas ditolak server', ['field' => $name, 'errors' => $errors, 'limit' => $limit]);

            $message = "Server menolak berkas. Biasanya karena ukurannya melebihi batas unggah server ({$limit}) "
                .'atau berkas rusak. Kecilkan/kompres gambar lalu pilih ulang berkas.';
        } else {
            Log::warning('Unggahan berkas terputus', ['field' => $name, 'limit' => $limit]);

            $message = 'Unggahan terputus sebelum selesai. Periksa koneksi internet lalu pilih ulang berkas. '
                ."Bila terus terjadi: pastikan ukuran berkas di bawah {$limit}, atau muat ulang halaman karena sesi mungkin sudah kedaluwarsa.";
        }

        throw ValidationException::withMessages([$name => $message]);
    }

    /** Pesan validasi saat tombol Unggah ditekan. */
    protected function uploadValidationMessages(string $field = 'newFile'): array
    {
        $limit = UploadLimit::label();

        return [
            "{$field}.required" => 'Belum ada berkas yang berhasil diunggah. Pilih berkas, tunggu sampai muncul "Berkas siap", lalu klik tombol unggah.',
            "{$field}.file" => 'Berkas tidak valid atau gagal diterima server. Pilih ulang berkas.',
            "{$field}.uploaded" => "Berkas gagal diterima server, kemungkinan melebihi batas {$limit}. Kecilkan ukuran berkas lalu coba lagi.",
            "{$field}.max" => "Ukuran berkas melebihi batas maksimal {$limit}.",
        ];
    }

    protected function uploadRule(): string
    {
        return 'required|file|max:'.intdiv(UploadLimit::maxBytes(), 1024);
    }
}
