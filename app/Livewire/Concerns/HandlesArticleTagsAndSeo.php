<?php

namespace App\Livewire\Concerns;

use App\Models\Article;
use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

/**
 * Kolom tag (dipisah koma, tag baru dibuat otomatis) dan daftar periksa SEO untuk editor artikel.
 * Dipakai ArticleCreate & ArticleEdit.
 */
trait HandlesArticleTagsAndSeo
{
    public string $tagInput = '';

    /** Kecamatan di Kabupaten Kebumen: dihitung sebagai penanda lokasi lokal di judul/paragraf pertama. */
    private const LOCAL_PLACES = [
        'kebumen', 'adimulyo', 'alian', 'ambal', 'ayah', 'bonorowo', 'buayan', 'buluspesantren', 'gombong',
        'karanganyar', 'karanggayam', 'karangsambung', 'klirong', 'kutowinangun', 'kuwarasan', 'mirit', 'padureso',
        'pejagoan', 'petanahan', 'poncowarno', 'prembun', 'puring', 'rowokele', 'sadang', 'sempor', 'sruweng',
    ];

    protected function tagRules(): array
    {
        return ['tagInput' => ['nullable', 'string', 'max:500']];
    }

    /** @return Collection<int, string> nama tag unik dari isian, maks. 10 */
    protected function parsedTags(): Collection
    {
        return collect(explode(',', $this->tagInput))
            ->map(fn ($name) => Str::limit(trim(preg_replace('/\s+/', ' ', $name)), 50, ''))
            ->filter(fn ($name) => Str::slug($name) !== '')
            ->unique(fn ($name) => Str::slug($name))
            ->take(10)
            ->values();
    }

    protected function syncArticleTags(Article $article): void
    {
        $ids = $this->parsedTags()->map(function (string $name) {
            $slug = Str::slug($name);

            return (Tag::where('slug', $slug)->first() ?? Tag::create(['name' => $name, 'slug' => $slug]))->id;
        });

        $article->tags()->sync($ids->all());
    }

    protected function loadArticleTags(Article $article): void
    {
        $this->tagInput = $article->tags()->orderBy('name')->pluck('name')->implode(', ');
    }

    /** Daftar tag yang sudah ada, untuk saran saat mengetik. */
    #[Computed]
    public function tagSuggestions(): Collection
    {
        return Tag::orderBy('name')->limit(300)->pluck('name');
    }

    /**
     * Daftar periksa SEO sederhana berdasarkan isian editor saat ini.
     *
     * @return array<int, array{label: string, ok: bool, hint: string}>
     */
    #[Computed]
    public function seoChecks(): array
    {
        $title = trim($this->title);
        $titleLength = mb_strlen($title);
        $text = trim(html_entity_decode(strip_tags(str_replace('</p>', "</p>\n", $this->content))));
        $words = $text === '' ? 0 : count(preg_split('/\s+/u', $text));
        $firstParagraph = Str::lower(strtok($text, "\n") ?: '');
        $summary = trim($this->meta_description ?: (string) $this->excerpt);
        $summaryLength = mb_strlen($summary);
        $haystack = Str::lower($title) . ' ' . $firstParagraph;
        $hasPlace = collect(self::LOCAL_PLACES)->contains(fn ($place) => preg_match('/\b' . $place . '\b/u', $haystack));
        $hasInternalLink = (bool) preg_match('#href="(https?://[^"]*' . preg_quote(parse_url(config('app.url'), PHP_URL_HOST) ?? '', '#') . ')?/(artikel|kategori|tag)/#i', $this->content);

        return [
            ['label' => 'Panjang judul 40–70 karakter', 'ok' => $titleLength >= 40 && $titleLength <= 70,
                'hint' => "Sekarang {$titleLength} karakter. Google menampilkan sekitar 60 karakter pertama."],
            ['label' => 'Menyebut Kebumen atau nama kecamatan di judul/paragraf pertama', 'ok' => $hasPlace,
                'hint' => 'Membantu artikel muncul saat orang mencari berita Kebumen.'],
            ['label' => 'Ringkasan atau meta description 100–160 karakter', 'ok' => $summaryLength >= 100 && $summaryLength <= 160,
                'hint' => "Sekarang {$summaryLength} karakter. Teks ini tampil di bawah judul pada hasil Google."],
            ['label' => 'Gambar sampul dipilih', 'ok' => (bool) $this->featured_image_id,
                'hint' => 'Wajib untuk tampil menarik di Google, WhatsApp, dan Facebook.'],
            ['label' => 'Minimal satu tag', 'ok' => $this->parsedTags()->isNotEmpty(),
                'hint' => 'Tag mengelompokkan berita bertopik sama di halaman /tag/.'],
            ['label' => 'Isi minimal 300 kata', 'ok' => $words >= 300,
                'hint' => "Sekarang {$words} kata."],
            ['label' => 'Ada tautan ke berita PortalKebumen lain', 'ok' => $hasInternalLink,
                'hint' => 'Tautkan berita lama yang berkaitan di dalam isi artikel.'],
        ];
    }
}
