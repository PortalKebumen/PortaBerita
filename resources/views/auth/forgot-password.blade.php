<x-layouts.guest title="Lupa Kata Sandi">
    <h1 class="mb-1.5 text-[19px] font-bold">Lupa kata sandi</h1>
    <p class="mb-6 text-[13px] text-[#6C7387]">Masukkan email akun admin. Kami akan mengirim tautan reset kata sandi.</p>

    @if (session('status'))
        <div class="alert-success mb-5">{{ session('status') }}</div>
    @endif

    <x-auth-countdown :until="session('password_retry_at')" message="Pengiriman ulang tautan reset dibatasi sementara." />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-6">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   placeholder="nama@portalkebumen.com"
                   class="form-input @error('email') is-error @enderror">
            @error('email')
                @if ((int) session('password_retry_at') <= now()->timestamp)
                    <div class="form-error-text" style="color: #dc2626;" role="alert">{{ $message }}</div>
                @endif
            @enderror
        </div>

        <button type="submit" class="btn-primary w-full justify-center">Kirim tautan reset</button>

        <div class="mt-5 text-center">
            <a href="{{ route('login') }}" class="text-[13px] font-semibold text-brand-600 hover:text-brand-700">
                Kembali ke login
            </a>
        </div>
    </form>
</x-layouts.guest>
