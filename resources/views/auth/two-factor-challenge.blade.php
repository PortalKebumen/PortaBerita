<x-layouts.guest title="Verifikasi Dua Langkah">
    <h1 class="text-[19px] font-bold mb-1.5">Verifikasi Dua Langkah</h1>
    <p class="text-[13px] text-[#6C7387] mb-6">Buka aplikasi authenticator di HP Anda, lalu masukkan kode 6 angka untuk PortalKebumen.</p>

    <form method="POST" action="{{ route('two-factor.challenge') }}">
        @csrf

        <div class="mb-4">
            <label for="code" class="form-label">Kode</label>
            <input id="code" type="text" name="code" required autofocus autocomplete="one-time-code" inputmode="numeric"
                   placeholder="123456"
                   class="form-input font-mono tracking-widest @error('code') is-error @enderror">
            @error('code')
                <div class="form-error-text" style="color: #dc2626;" role="alert">{{ $message }}</div>
            @enderror
            <p class="text-[12px] text-[#6C7387] mt-2">HP hilang atau rusak? Masukkan salah satu <strong>kode cadangan</strong> (format XXXXX-XXXXX) yang Anda simpan saat mengaktifkan verifikasi dua langkah.</p>
        </div>

        <button type="submit" class="btn-primary w-full justify-center">Verifikasi</button>
    </form>

    <p class="mt-5 text-center text-[13px]">
        <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">Kembali ke halaman masuk</a>
    </p>
</x-layouts.guest>
