<?php

namespace Tests\Feature;

use App\Livewire\Admin\ArticleCreate;
use App\Livewire\Admin\ArticleEdit;
use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Notifications\SystemAlertNotification;
use App\Support\ErrorNotifier;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class EditorialToolsTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function userWithRole(string $role): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_writer_can_add_tags_and_new_tags_are_created_without_duplicates(): void
    {
        $writer = $this->userWithRole('Penulis');
        $category = Category::create(['name' => 'Berita Kebumen']);
        Tag::create(['name' => 'Prameks']);

        Livewire::actingAs($writer)->test(ArticleCreate::class)
            ->set('title', 'Jadwal Commuter Line Prameks dari Stasiun Kebumen Mulai Desember')
            ->set('slug', 'jadwal-prameks')
            ->set('content', str_repeat('<p>Isi berita tentang jadwal kereta di Kebumen.</p>', 3))
            ->set('category_id', $category->id)
            ->set('tagInput', 'prameks, Kereta Api ,  , Kereta api')
            ->call('save')
            ->assertHasNoErrors();

        $article = Article::where('slug', 'jadwal-prameks')->firstOrFail();
        $this->assertEqualsCanonicalizing(['Prameks', 'Kereta Api'], $article->tags->pluck('name')->all());
        $this->assertSame(2, Tag::count(), 'Tag "prameks" memakai tag Prameks yang sudah ada');

        Livewire::actingAs($writer)->test(ArticleEdit::class, ['article' => $article])
            ->assertSet('tagInput', 'Kereta Api, Prameks')
            ->set('tagInput', 'Prameks')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame(['Prameks'], $article->fresh()->tags->pluck('name')->all());
    }

    public function test_seo_checklist_reflects_editor_fields(): void
    {
        $writer = $this->userWithRole('Penulis');

        $checks = fn ($component) => collect($component->instance()->seoChecks)->pluck('ok', 'label');

        $empty = Livewire::actingAs($writer)->test(ArticleCreate::class)->set('title', 'Judul pendek');
        $this->assertSame(0, $checks($empty)->filter()->count());

        $filled = Livewire::actingAs($writer)->test(ArticleCreate::class)
            ->set('title', 'Warga Gombong Sambut Jadwal Baru Kereta Prameks Desember')
            ->set('excerpt', str_repeat('Ringkasan berita kereta untuk warga. ', 4))
            ->set('tagInput', 'Prameks')
            ->set('content', '<p>Pembuka.</p>' . str_repeat('<p>' . str_repeat('kata ', 50) . '</p>', 6) . '<p><a href="/artikel/lama">berita lama</a></p>');
        $result = $checks($filled);

        $this->assertTrue($result['Panjang judul 40–70 karakter']);
        $this->assertTrue($result['Menyebut Kebumen atau nama kecamatan di judul/paragraf pertama']);
        $this->assertTrue($result['Ringkasan atau meta description 100–160 karakter']);
        $this->assertTrue($result['Minimal satu tag']);
        $this->assertTrue($result['Isi minimal 300 kata']);
        $this->assertTrue($result['Ada tautan ke berita PortalKebumen lain']);
        $this->assertFalse($result['Gambar sampul dipilih']);
    }

    public function test_dashboard_shows_weekly_publishing_target(): void
    {
        $editor = $this->userWithRole('Editor');
        \App\Models\SiteSetting::setMany(['weekly_article_target' => '5']);
        $category = Category::create(['name' => 'Berita Kebumen']);
        Article::create([
            'title' => 'Terbit minggu ini', 'slug' => 'terbit', 'content' => '<p>Isi.</p>', 'status' => 'published',
            'published_at' => now()->startOfWeek()->addHour()->min(now()), 'author_id' => $editor->id, 'category_id' => $category->id,
        ]);

        $this->actingAs($editor)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Target terbit minggu ini')
            ->assertSeeInOrder(['1', 'dari 5 artikel'])
            ->assertSee('Kurang 4 artikel');
    }

    public function test_production_errors_notify_admins_once_and_respect_hourly_cap(): void
    {
        Notification::fake();
        $admin = $this->userWithRole('Super Admin');
        $this->app['env'] = 'production';

        $error = new \RuntimeException('Koneksi gagal');
        ErrorNotifier::notify($error);
        ErrorNotifier::notify($error); // error yang sama: tidak dikirim ulang

        Notification::assertSentToTimes($admin, SystemAlertNotification::class, 1);

        // Error berbeda (jenis/lokasi berbeda) dihitung terpisah
        ErrorNotifier::notify(new \LogicException('Error lain 1'));
        ErrorNotifier::notify(new \InvalidArgumentException('Error lain 2'));
        ErrorNotifier::notify(new \DomainException('Error lain 3'));
        ErrorNotifier::notify(new \LengthException('Error lain 4'));
        // Batas 3 notifikasi per jam (1 di atas + 2 lagi)
        Notification::assertSentToTimes($admin, SystemAlertNotification::class, 3);
    }
}
