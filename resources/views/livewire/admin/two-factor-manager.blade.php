<div class="space-y-6 max-w-3xl">
    @if ($recoveryCodes)
        <div class="card card-body space-y-3">
            <h2 class="text-[15px] font-bold">Kode cadangan</h2>
            <p class="text-[13px] text-[#3B4152]">Simpan kode di bawah ini di tempat aman (catat di buku atau simpan di pengelola kata sandi). Setiap kode hanya bisa dipakai <strong>sekali</strong> untuk masuk bila HP Anda hilang atau rusak. Kode ini hanya ditampilkan sekarang.</p>
            <div class="grid grid-cols-2 gap-2 rounded-lg border border-[#E4E8EF] bg-[#F7F8FA] p-4 font-mono text-[14px] sm:grid-cols-4">
                @foreach ($recoveryCodes as $recoveryCode)
                    <span>{{ $recoveryCode }}</span>
                @endforeach
            </div>
            <div>
                <a href="{{ route('admin.dashboard') }}" class="btn-primary">Sudah saya simpan, lanjut ke Dasbor</a>
            </div>
        </div>
    @endif

    @if (! $enabled)
        <div class="alert-warning">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 mt-0.5"><path d="M12 9v4M12 17h.01"></path><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"></path></svg>
            <div>Verifikasi dua langkah <strong class="font-semibold">wajib</strong> untuk semua akun. Selesaikan langkah di bawah ini sebelum memakai menu lain.</div>
        </div>

        <form wire:submit="confirm" class="card card-body space-y-5">
            <div>
                <h2 class="text-[15px] font-bold">Aktifkan verifikasi dua langkah</h2>
                <p class="text-[12.5px] text-[#6C7387] mt-1">Setiap kali masuk, selain kata sandi Anda diminta kode 6 angka dari aplikasi di HP.</p>
            </div>

            <ol class="list-decimal pl-5 space-y-4 text-[13.5px] text-[#1B2033]">
                <li>Pasang aplikasi <strong>Google Authenticator</strong> (atau Microsoft Authenticator) dari Play Store / App Store.</li>
                <li>
                    Di aplikasi, ketuk tombol <strong>+</strong> lalu <strong>Pindai kode QR</strong>, arahkan ke kode berikut:
                    <div class="mt-3 inline-block rounded-lg border border-[#E4E8EF] bg-white p-3">{!! $qr !!}</div>
                    <p class="mt-2 text-[12px] text-[#6C7387]">Tidak bisa memindai? Pilih <em>Masukkan kunci penyiapan</em> dan ketik kunci ini:
                        <span class="block mt-1 font-mono text-[13px] text-[#1B2033] break-all">{{ $secret }}</span></p>
                </li>
                <li>
                    Masukkan kode 6 angka yang muncul di aplikasi:
                    <div class="mt-2 flex flex-col sm:flex-row gap-2.5 sm:items-start">
                        <div>
                            <input type="text" wire:model="code" inputmode="numeric" autocomplete="one-time-code" placeholder="123456"
                                   class="form-input font-mono tracking-widest w-40 @error('code') is-error @enderror">
                            @error('code') <p class="form-error-text">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="confirm">Aktifkan</button>
                    </div>
                </li>
            </ol>
        </form>
    @else
        <div class="card card-body space-y-4">
            <div>
                <h2 class="text-[15px] font-bold">Verifikasi dua langkah aktif</h2>
                <p class="text-[12.5px] text-[#6C7387] mt-1">Aktif sejak {{ $confirmedAt?->translatedFormat('d F Y, H:i') }} WIB. Sisa kode cadangan: <strong>{{ $remainingCodes }}</strong> dari {{ \App\Services\TwoFactor::RECOVERY_CODES }}.</p>
            </div>

            <form wire:submit="regenerateRecoveryCodes" class="border-t border-[#E4E8EF] pt-4">
                <label class="form-label">Buat ulang kode cadangan</label>
                <p class="text-[12px] text-[#6C7387] mb-2">Pakai bila kode cadangan hilang atau tinggal sedikit. Masukkan kode 6 angka dari aplikasi untuk konfirmasi.</p>
                <div class="flex flex-col sm:flex-row gap-2.5 sm:items-start">
                    <div>
                        <input type="text" wire:model="code" inputmode="numeric" autocomplete="one-time-code" placeholder="123456"
                               class="form-input font-mono tracking-widest w-40 @error('code') is-error @enderror">
                        @error('code') <p class="form-error-text">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn-secondary" wire:loading.attr="disabled" wire:target="regenerateRecoveryCodes">Buat ulang</button>
                </div>
            </form>

            <p class="text-[12px] text-[#6C7387] border-t border-[#E4E8EF] pt-4">Ganti HP? Minta Super Admin mereset verifikasi dua langkah akun Anda di menu Pengguna & Role, lalu aktifkan lagi dengan HP baru.</p>
        </div>
    @endif
</div>
