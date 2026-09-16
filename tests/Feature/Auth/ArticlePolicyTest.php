<?php

namespace Tests\Feature\Auth;

use App\Models\Article;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ArticlePolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @return array<string, array{string|null}> */
    public static function statuses(): array
    {
        return [
            'draft' => ['draft'],
            'pending review' => ['pending_review'],
            'review' => ['review'],
            'approved' => ['approved'],
            'rejected' => ['rejected'],
            'revision' => ['revision'],
            'published' => ['published'],
            'scheduled' => ['scheduled'],
            'archived' => ['archived'],
            'unknown' => ['unknown'],
            'missing' => [null],
        ];
    }

    #[DataProvider('statuses')]
    public function test_writer_can_only_change_own_draft_and_cannot_perform_editorial_actions(?string $status): void
    {
        $this->seed(RolePermissionSeeder::class);
        $writer = User::factory()->create();
        $other = User::factory()->create();
        $writer->assignRole('Penulis');
        $own = new Article(['author_id' => (string) $writer->id, 'status' => $status]);
        $foreign = new Article(['author_id' => $other->id, 'status' => $status]);
        $gate = Gate::forUser($writer);

        $this->assertTrue($gate->allows('view', $own));
        $this->assertFalse($gate->allows('view', $foreign));
        foreach (['update', 'delete'] as $ability) {
            $this->assertSame($status === 'draft', $gate->allows($ability, $own), $ability);
            $this->assertFalse($gate->allows($ability, $foreign), $ability);
        }
        foreach (['approve', 'reject', 'publish'] as $ability) {
            $this->assertFalse($gate->allows($ability, $own), $ability);
            $this->assertFalse($gate->allows($ability, $foreign), $ability);
        }
    }

    #[DataProvider('statuses')]
    public function test_editor_can_manage_articles_from_multiple_writers_without_delete_any_access(?string $status): void
    {
        $this->seed(RolePermissionSeeder::class);
        $editor = User::factory()->create();
        $editor->assignRole('Editor');
        $gate = Gate::forUser($editor);

        foreach (User::factory()->count(2)->create() as $writer) {
            $writer->assignRole('Penulis');
            $article = new Article(['author_id' => $writer->id, 'status' => $status]);
            foreach (['view', 'update', 'approve', 'reject', 'publish'] as $ability) {
                $this->assertTrue($gate->allows($ability, $article), $ability);
            }
            $this->assertFalse($gate->allows('delete', $article));
        }
    }

    public function test_guests_ads_managers_and_users_without_permissions_are_denied_even_for_own_drafts(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $unprivileged = User::factory()->create();
        $adsManager = User::factory()->create();
        $adsManager->assignRole('Ads Manager');

        foreach ([null, $unprivileged, $adsManager] as $user) {
            $article = new Article(['author_id' => $user?->id, 'status' => 'draft']);
            foreach (['view', 'update', 'delete', 'approve', 'reject', 'publish'] as $ability) {
                $this->assertFalse(Gate::forUser($user)->allows($ability, $article), $ability);
            }
        }
    }

    public function test_super_admin_can_delete_another_authors_published_article(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $author = User::factory()->create();
        $article = new Article(['author_id' => $author->id, 'status' => 'published']);

        foreach (['view', 'update', 'delete', 'approve', 'reject', 'publish'] as $ability) {
            $this->assertTrue(Gate::forUser($admin)->allows($ability, $article), $ability);
        }
    }

    public function test_direct_permissions_and_revocation_control_article_access(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $article = new Article(['author_id' => $user->id, 'status' => 'draft']);
        $permissions = [
            'view' => 'articles.view-own',
            'update' => 'articles.update-own-draft',
            'delete' => 'articles.delete-own-draft',
            'approve' => 'articles.approve',
            'reject' => 'articles.reject',
            'publish' => 'articles.publish',
        ];

        foreach ($permissions as $ability => $permission) {
            $user->givePermissionTo($permission);
            $this->assertTrue(Gate::forUser($user)->allows($ability, $article), $ability);
            $user->revokePermissionTo($permission);
            $this->assertFalse(Gate::forUser($user)->allows($ability, $article), $ability);
        }
    }

    public function test_missing_author_never_grants_ownership_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $writer = User::factory()->create();
        $writer->assignRole('Penulis');
        $article = new Article(['status' => 'draft']);

        foreach (['view', 'update', 'delete'] as $ability) {
            $this->assertFalse(Gate::forUser($writer)->allows($ability, $article), $ability);
        }
    }
}
