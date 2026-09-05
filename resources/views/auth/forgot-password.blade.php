<x-layouts.guest title="Lupa Kata Sandi">
    <h1 class="mb-1.5 text-[19px] font-bold">Lupa kata sandi</h1>
    <p class="mb-6 text-[13px] text-[#6C7387]">Masukkan email akun admin. Kami akan mengirim tautan reset kata sandi.</p>

    @if (session('status'))
        <div class="alert-success mb-5">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-6">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   placeholder="nama@portalkebumen.com"
                   class="form-input @error('email') is-error @enderror">
            @error('email')
                <div class="form-error-text">{{ $message }}</div>
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
