<div class="space-y-6 max-w-3xl">
    @if ($mustChange)
        <div class="alert-warning">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 mt-0.5"><path d="M12 9v4M12 17h.01"></path><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"></path></svg>
            <div>Anda masuk memakai <strong class="font-semibold">kata sandi sementara</strong> dari administrator. Ganti kata sandi di bawah ini terlebih dahulu sebelum memakai menu lain.</div>
        </div>
    @endif

    {{-- Ringkasan akun --}}
    <div class="card card-body flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-brand-700 text-white text-[16px] font-bold flex items-center justify-center shrink-0">
            {{ collect(explode(' ', $user->name))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') }}
        </div>
        <div class="min-w-0">
            <div class="text-[16px] font-bold truncate">{{ $user->name }}</div>
            <div class="text-[13px] text-[#6C7387] truncate">{{ $user->email }}</div>
            <div class="mt-1 flex flex-wrap items-center gap-2 text-[12px]">
                @if ($roleName)
                    <span class="badge-info">{{ $roleName }}</span>
                @endif
                @can('articles.create')
                    <a href="{{ $user->profileUrl() }}" target="_blank" rel="noopener" class="text-brand-600 hover:underline">Lihat profil publik</a>
                @endcan
            </div>
        </div>
    </div>

    {{-- Informasi profil --}}
    <form wire:submit="saveProfile" class="card card-body space-y-5">
        <div>
            <h2 class="text-[15px] font-bold">Informasi Profil</h2>
            <p class="text-[12.5px] text-[#6C7387] mt-1">Nama dan bio tampil di halaman profil penulis dan di bawah artikel Anda.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label" for="profile-name">Nama Lengkap <span class="text-danger">*</span></label>
                <input id="profile-name" type="text" wire:model="name" autocomplete="name" class="form-input @error('name') is-error @enderror">
                @error('name') <p class="form-error-text">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label" for="profile-email">Email <span class="text-danger">*</span></label>
                <input id="profile-email" type="email" wire:model="email" autocomplete="email" class="form-input @error('email') is-error @enderror">
                @error('email') <p class="form-error-text">{{ $message }}</p> @enderror
                <p class="text-[11.5px] text-[#848CA3] mt-1">Dipakai untuk login dan menerima email reset kata sandi.</p>
            </div>
        </div>

        <div>
            <label class="form-label" for="profile-bio">Bio Singkat</label>
            <textarea id="profile-bio" wire:model="bio" rows="4" maxlength="1000" class="form-input @error('bio') is-error @enderror" placeholder="Contoh: Jurnalis PortalKebumen yang meliput isu pendidikan dan pemerintahan daerah."></textarea>
            @error('bio') <p class="form-error-text">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="saveProfile">
                <span wire:loading.remove wire:target="saveProfile">Simpan Profil</span>
                <span wire:loading wire:target="saveProfile">Menyimpan...</span>
            </button>
        </div>
    </form>

    {{-- Ganti kata sandi --}}
    <form wire:submit="savePassword" class="card card-body space-y-5" id="ganti-kata-sandi">
        <div>
            <h2 class="text-[15px] font-bold">Ganti Kata Sandi</h2>
            <p class="text-[12.5px] text-[#6C7387] mt-1">Minimal 8 karakter, memuat huruf dan angka.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div>
                <label class="form-label" for="current-password">Kata Sandi Saat Ini</label>
                <input id="current-password" type="password" wire:model="currentPassword" autocomplete="current-password" class="form-input @error('currentPassword') is-error @enderror">
                @error('currentPassword') <p class="form-error-text">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label" for="new-password">Kata Sandi Baru</label>
                <input id="new-password" type="password" wire:model="newPassword" autocomplete="new-password" class="form-input @error('newPassword') is-error @enderror">
                @error('newPassword') <p class="form-error-text">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label" for="new-password-confirmation">Ulangi Kata Sandi Baru</label>
                <input id="new-password-confirmation" type="password" wire:model="newPassword_confirmation" autocomplete="new-password" class="form-input">
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="savePassword">
                <span wire:loading.remove wire:target="savePassword">Ganti Kata Sandi</span>
                <span wire:loading wire:target="savePassword">Menyimpan...</span>
            </button>
        </div>
    </form>
</div>
