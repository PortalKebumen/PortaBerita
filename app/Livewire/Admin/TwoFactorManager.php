<?php

namespace App\Livewire\Admin;

use App\Services\TwoFactor;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Keamanan Akun: aktifkan verifikasi dua langkah (pindai QR, konfirmasi kode) dan kelola kode cadangan.
 * Kunci sementara disimpan di sesi sampai dikonfirmasi; baru setelah kode benar disimpan ke akun.
 */
class TwoFactorManager extends Component
{
    private const PENDING_KEY = 'two_factor.pending_secret';

    public string $code = '';

    /** Kode cadangan yang baru dibuat: hanya ditampilkan sekali. */
    public array $recoveryCodes = [];

    public function confirm(TwoFactor $twoFactor): void
    {
        $user = Auth::user();
        $secret = session(self::PENDING_KEY);
        if (! $secret) {
            $this->addError('code', 'Kode QR kedaluwarsa. Muat ulang halaman lalu pindai lagi.');

            return;
        }

        $this->validate(['code' => ['required', 'string', 'max:10']], [], ['code' => 'kode']);

        if (! $twoFactor->verify($secret, $this->code, $user->id)) {
            $this->addError('code', 'Kode tidak cocok. Pastikan jam HP otomatis, lalu masukkan kode terbaru.');

            return;
        }

        $this->recoveryCodes = $twoFactor->enable($user, $secret);
        session()->forget(self::PENDING_KEY);
        $this->reset('code');

        activity('pengguna')->causedBy($user)->performedOn($user)->log('Mengaktifkan verifikasi dua langkah');
        $this->dispatch('flash-message', type: 'success', text: 'Verifikasi dua langkah aktif. Simpan kode cadangan Anda.');
    }

    public function regenerateRecoveryCodes(TwoFactor $twoFactor): void
    {
        $user = Auth::user();
        $this->validate(['code' => ['required', 'string', 'max:10']], [], ['code' => 'kode']);

        if (! $user->hasTwoFactorEnabled() || ! $twoFactor->verify($user->two_factor_secret, $this->code, $user->id)) {
            $this->addError('code', 'Kode tidak cocok.');

            return;
        }

        $this->recoveryCodes = $twoFactor->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $this->recoveryCodes])->save();
        $this->reset('code');

        activity('pengguna')->causedBy($user)->performedOn($user)->log('Membuat ulang kode cadangan verifikasi dua langkah');
        $this->dispatch('flash-message', type: 'success', text: 'Kode cadangan baru dibuat. Kode lama tidak berlaku lagi.');
    }

    public function render(TwoFactor $twoFactor)
    {
        $user = Auth::user();
        $qr = null;
        $secret = null;

        if (! $user->hasTwoFactorEnabled()) {
            $secret = session(self::PENDING_KEY) ?? tap($twoFactor->generateSecret(), fn ($s) => session([self::PENDING_KEY => $s]));
            $qr = $twoFactor->qrCodeSvg($user, $secret);
        }

        return view('livewire.admin.two-factor-manager', [
            'enabled' => $user->hasTwoFactorEnabled(),
            'confirmedAt' => $user->two_factor_confirmed_at,
            'remainingCodes' => count($user->two_factor_recovery_codes ?? []),
            'qr' => $qr,
            'secret' => $secret ? trim(chunk_split($secret, 4, ' ')) : null,
        ]);
    }
}
