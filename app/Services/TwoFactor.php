<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Verifikasi dua langkah (TOTP, standar Google Authenticator/Microsoft Authenticator/Authy).
 * Kode 6 angka berganti tiap 30 detik; toleransi 1 langkah (±30 detik) untuk selisih jam HP.
 */
class TwoFactor
{
    public const RECOVERY_CODES = 8;

    public function __construct(private Google2FA $engine = new Google2FA) {}

    public function generateSecret(): string
    {
        return $this->engine->generateSecretKey(32);
    }

    /** Kode QR (SVG) untuk dipindai aplikasi authenticator. */
    public function qrCodeSvg(User $user, string $secret): string
    {
        $issuer = \App\Models\SiteSetting::get('site_name', config('app.name'));
        $url = $this->engine->getQRCodeUrl($issuer, $user->email, $secret);

        $svg = (new Writer(new ImageRenderer(new RendererStyle(200, 1), new SvgImageBackEnd)))->writeString($url);

        return trim(substr($svg, strpos($svg, "\n") + 1)); // tanpa deklarasi <?xml agar bisa disisipkan di HTML
    }

    /**
     * Cocokkan kode dari aplikasi. Kode yang sudah dipakai tidak bisa dipakai lagi (mencegah pemakaian ulang
     * kode yang terlihat/tersadap dalam jendela 30 detik yang sama).
     */
    public function verify(string $secret, string $code, ?int $userId = null): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== 6 || ! $this->engine->verifyKey($secret, $code, 1)) {
            return false;
        }

        return $userId === null || Cache::add("2fa-used:{$userId}:{$code}", true, now()->addSeconds(120));
    }

    /** @return array<int, string> kode cadangan sekali pakai, format XXXXX-XXXXX */
    public function generateRecoveryCodes(): array
    {
        return collect(range(1, self::RECOVERY_CODES))
            ->map(fn () => Str::upper(Str::random(5) . '-' . Str::random(5)))
            ->all();
    }

    /** Pakai satu kode cadangan (hapus dari daftar bila cocok). */
    public function useRecoveryCode(User $user, string $code): bool
    {
        $code = Str::upper(trim($code));
        $codes = $user->two_factor_recovery_codes ?? [];

        if (! in_array($code, $codes, true)) {
            return false;
        }

        $user->forceFill(['two_factor_recovery_codes' => array_values(array_diff($codes, [$code]))])->save();

        return true;
    }

    public function enable(User $user, string $secret): array
    {
        $codes = $this->generateRecoveryCodes();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $codes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $codes;
    }

    public function reset(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }
}
