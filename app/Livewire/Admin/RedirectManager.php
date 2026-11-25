<?php

namespace App\Livewire\Admin;

use App\Models\Redirect;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Pengaturan > Pengalihan: daftar pengalihan 301 (otomatis dari slug artikel yang diganti, atau manual
 * untuk alamat 404 yang ditemukan di Google Search Console).
 */
class RedirectManager extends Component
{
    use WithPagination;

    public string $oldUrl = '';

    public string $newUrl = '';

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('settings.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $this->authorize('settings.update');

        $this->oldUrl = trim($this->oldUrl) === '' ? '' : Redirect::normalizePath($this->oldUrl);
        $this->newUrl = $this->normalizeTarget($this->newUrl);

        $this->validate([
            'oldUrl' => ['required', 'max:255', 'not_in:/', Rule::unique('redirects', 'old_url'), 'different:newUrl', 'not_regex:#^/admin(/|$)#'],
            'newUrl' => ['required', 'max:255', 'regex:#^(/|https?://)#'],
        ], [
            'oldUrl.unique' => 'Alamat lama ini sudah punya pengalihan.',
            'oldUrl.not_in' => 'Beranda tidak bisa dialihkan.',
            'oldUrl.different' => 'Alamat lama dan alamat tujuan tidak boleh sama.',
            'oldUrl.not_regex' => 'Alamat admin tidak bisa dialihkan.',
            'newUrl.regex' => 'Alamat tujuan harus diawali "/" atau "https://".',
        ], ['oldUrl' => 'alamat lama', 'newUrl' => 'alamat tujuan']);

        Redirect::create(['old_url' => $this->oldUrl, 'new_url' => $this->newUrl, 'status_code' => 301]);

        $this->reset('oldUrl', 'newUrl');
        $this->dispatch('flash-message', type: 'success', text: 'Pengalihan berhasil ditambahkan.');
    }

    public function delete(int $id): void
    {
        $this->authorize('settings.update');

        Redirect::whereKey($id)->delete();
        $this->dispatch('flash-message', type: 'success', text: 'Pengalihan dihapus.');
    }

    /** Alamat tujuan di situs sendiri disimpan sebagai path; alamat situs lain tetap URL lengkap. */
    private function normalizeTarget(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $bare = fn (?string $host) => preg_replace('/^www\./', '', strtolower((string) $host));
        $host = parse_url($url, PHP_URL_HOST);
        if ($host && $bare($host) !== $bare(parse_url(config('app.url'), PHP_URL_HOST))) {
            return $url;
        }

        return Redirect::normalizePath($url);
    }

    public function render()
    {
        return view('livewire.admin.redirect-manager', [
            'redirects' => Redirect::query()
                ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->where('old_url', 'like', "%{$this->search}%")
                    ->orWhere('new_url', 'like', "%{$this->search}%")))
                ->orderByDesc('id')
                ->paginate(20),
        ]);
    }
}
