<?php

namespace Tests\Feature\Auth;

use App\Models\Article;
use App\Models\ArticleStatus;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ArticlePolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @return array<string, array{?ArticleStatus}> */
    public static function statuses(): array
    {
        return [
            'draft' => [ArticleStatus::Draft],
            'submitted' => [ArticleStatus::Submitted],
            'approved' => [ArticleStatus::Approved],
            'rejected' => [ArticleStatus::Rejected],
            'published' => [ArticleStatus::Published],
            'archived' => [ArticleStatus::Archived],
            'missing' => [null],
        ];
    }

    /**
     * status => [canApprove, canReject, canPublish] untuk Editor.
     *
     * @return array<string, array{?ArticleStatus, bool, bool, bool}>
     */
    public static function editorialMatrix(): array
    {
        return [
            'draft' => [ArticleStatus::Draft, false, false, false],
            'submitted' => [ArticleStatus::Submitted, true, true, false],
            'approved' => [ArticleStatus::Approved, false, true, true],
            'rejected' => [ArticleStatus::Rejected, false, false, false],
            'published' => [ArticleStatus::Published, false, false, false],
            'archived' => [ArticleStatus::Archived, false, false, false],
            'missing' => [null, false, false, false],
        ];
    }

    #[DataProvider('statuses')]
    public function test_writer_can_only_change_own_draft_or_rejected_and_cannot_perform_editorial_actions(?ArticleStatus $status): void
    {
        $this->seed(RolePermissionSeeder::class);
        $writer = User::factory()->create();
        $other = User::factory()->create();
        $writer->assignRole('Penulis');
        $own = new Article(['author_id' => (string) $writer->id, 'status' => $status]);
        $foreign = new Article(['author_id' => $other->id, 'status' => $status]);
        $gate = Gate::forUser($writer);

        $editable = in_array($status, [ArticleStatus::Draft, ArticleStatus::Rejected], true);
        $deletable = $status === ArticleStatus::Draft;

        $this->assertTrue($gate->allows('view', $own));
        $this->assertFalse($gate->allows('view', $foreign));

        $this->assertSame($editable, $gate->allows('update', $own), 'update');
        $this->assertSame($deletable, $gate->allows('delete', $own), 'delete');
        $this->assertFalse($gate->allows('update', $foreign), 'update foreign');
        $this->assertFalse($gate->allows('delete', $foreign), 'delete foreign');

        foreach (['approve', 'reject', 'publish'] as $ability) {
            $this->assertFalse($gate->allows($ability, $own), $ability);
            $this->assertFalse($gate->allows($ability, $foreign), $ability);
        }
    }

    #[DataProvider('editorialMatrix')]
    public function test_editor_can_manage_articles_from_multiple_writers_without_delete_any_access(
        ?ArticleStatus $status,
        bool $canApprove,
        bool $canReject,
        bool $canPublish,
    ): void {
        $this->seed(RolePermissionSeeder::class);
        $editor = User::factory()->create();
        $editor->assignRole('Editor');
        $gate = Gate::forUser($editor);

        foreach (User::factory()->count(2)->create() as $writer) {
            $writer->assignRole('Penulis');
            $article = new Article(['author_id' => $writer->id, 'status' => $status]);

            $this->assertTrue($gate->allows('view', $article), 'view');
            $this->assertTrue($gate->allows('update', $article), 'update');
            $this->assertSame($canApprove, $gate->allows('approve', $article), 'approve');
            $this->assertSame($canReject, $gate->allows('reject', $article), 'reject');
            $this->assertSame($canPublish, $gate->allows('publish', $article), 'publish');
            $this->assertFalse($gate->allows('delete', $article), 'delete');
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

        // ability => [permission, status artikel yang valid untuk ability itu]
        $cases = [
            'view' => ['articles.view-own', ArticleStatus::Draft],
            'update' => ['articles.update-own-draft', ArticleStatus::Draft],
            'delete' => ['articles.delete-own-draft', ArticleStatus::Draft],
            'approve' => ['articles.approve', ArticleStatus::Submitted],
            'reject' => ['articles.reject', ArticleStatus::Submitted],
            'publish' => ['articles.publish', ArticleStatus::Approved],
        ];

        foreach ($cases as $ability => [$permission, $status]) {
            $article = new Article(['author_id' => $user->id, 'status' => $status]);

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