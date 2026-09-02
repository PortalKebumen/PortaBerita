<x-layouts.guest title="Masuk">
    <h1 class="text-[19px] font-bold mb-1.5">Masuk ke Admin</h1>
    <p class="text-[13px] text-[#6C7387] mb-6">Gunakan akun yang terdaftar untuk mengakses panel admin.</p>

    @if (session('status'))
        <div class="alert-success mb-5">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-4">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   placeholder="nama@portalkebumen.com"
                   class="form-input @error('email') is-error @enderror">
            @error('email')
                <div class="form-error-text">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="password" class="form-label">Kata Sandi</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   placeholder="••••••••"
                   class="form-input @error('password') is-error @enderror">
            @error('password')
                <div class="form-error-text">{{ $message }}</div>
            @enderror
        </div>

        <label class="flex items-center gap-2.5 cursor-pointer mb-6">
            <input type="checkbox" name="remember" class="form-checkbox">
            <span class="text-[13px]">Ingat saya di perangkat ini</span>
        </label>

        <button type="submit" class="btn-primary w-full justify-center">Masuk</button>
    </form>
</x-layouts.guest>