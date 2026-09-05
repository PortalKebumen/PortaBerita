<x-layouts.guest title="Reset Kata Sandi">
    <h1 class="mb-1.5 text-[19px] font-bold">Reset kata sandi</h1>
    <p class="mb-6 text-[13px] text-[#6C7387]">Buat kata sandi baru untuk akun PortalKebumen kamu.</p>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-4">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autofocus autocomplete="username"
                   placeholder="nama@portalkebumen.com"
                   class="form-input @error('email') is-error @enderror">
            @error('email')
                <div class="form-error-text">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="password" class="form-label">Kata Sandi Baru</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                   placeholder="Masukkan kata sandi baru"
                   class="form-input @error('password') is-error @enderror">
            @error('password')
                <div class="form-error-text">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-6">
            <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                   placeholder="Ulangi kata sandi baru"
                   class="form-input">
        </div>

        <button type="submit" class="btn-primary w-full justify-center">Simpan kata sandi baru</button>
    </form>
</x-layouts.guest>
