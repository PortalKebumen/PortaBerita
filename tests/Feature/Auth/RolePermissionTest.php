<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleTestUserSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_demo_accounts_can_login_with_their_individual_roles(): void
    {
        $this->seed(RoleTestUserSeeder::class);
        $this->seed(RoleTestUserSeeder::class);
        $this->assertDatabaseCount('users', 3);

        foreach ([
            'editor@portalkebumen.test' => 'Editor',
            'penulis@portalkebumen.test' => 'Penulis',
            'ads@portalkebumen.test' => 'Ads Manager',
        ] as $email => $role) {
            $this->post(route('login'), ['email' => $email, 'password' => 'password'])
                ->assertRedirectToRoute('admin.dashboard');
            $user = User::where('email', $email)->firstOrFail();
            $this->assertAuthenticatedAs($user);
            $this->assertSame([$role], $user->getRoleNames()->all());
            $this->get(route('admin.dashboard'))->assertOk();
            $this->post(route('logout'));
        }
    }

    public function test_demo_seeder_preserves_existing_accounts(): void
    {
        $user = User::factory()->create(['email' => 'editor@portalkebumen.test']);
        $password = $user->password;

        $this->seed(RoleTestUserSeeder::class);

        $this->assertSame($password, $user->fresh()->password);
        $this->assertSame([], $user->fresh()->getRoleNames()->all());
    }

    public function test_demo_seeder_does_not_create_accounts_in_production(): void
    {
        $this->app->instance('env', 'production');

        app(RoleTestUserSeeder::class)->run();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_permission_migration_and_repeatable_seeding_preserve_assignments(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Penulis');
        Role::findByName('Penulis')->givePermissionTo('articles.publish');
        $permissionCount = Permission::count();

        $this->seed(RolePermissionSeeder::class);

        foreach (['roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }
        $this->assertDatabaseCount('roles', 4);
        $this->assertDatabaseCount('permissions', $permissionCount);
        $this->assertTrue($user->fresh()->hasRole('Penulis'));
        $this->assertFalse($user->fresh()->can('articles.publish'));
        $this->assertSame(['web'], Role::distinct()->pluck('guard_name')->all());
        $this->assertSame(['web'], Permission::distinct()->pluck('guard_name')->all());
    }

    public function test_database_seeder_assigns_super_admin_without_resetting_existing_account(): void
    {
        $user = User::factory()->create(['email' => 'admin@portalkebumen.test']);
        $password = $user->password;

        $this->seed(DatabaseSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($password, $user->fresh()->password);
        $this->assertTrue($user->fresh()->hasRole('Super Admin'));
        $this->assertTrue($user->fresh()->can('roles.assign'));
    }

    /** @return array<string, array{string, list<string>}> */
    public static function roleRoutes(): array
    {
        return [
            'administrator' => ['Super Admin', ['dashboard', 'artikel.index', 'kategori-tag.index', 'media-library.index', 'iklan.index', 'pengguna-role.index', 'activity-log.index', 'pengaturan.index']],
            'editor' => ['Editor', ['dashboard', 'artikel.index', 'kategori-tag.index', 'media-library.index', 'activity-log.index']],
            'writer' => ['Penulis', ['dashboard', 'artikel.index', 'media-library.index']],
            'ads manager' => ['Ads Manager', ['dashboard', 'iklan.index']],
        ];
    }

    #[DataProvider('roleRoutes')]
    public function test_admin_routes_and_navigation_follow_permissions(string $role, array $allowedRoutes): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);

        $dashboard = $this->get(route('admin.dashboard'))->assertOk();

        foreach (self::roleRoutes()['administrator'][1] as $route) {
            $url = route('admin.'.$route);
            if (in_array($route, $allowedRoutes, true)) {
                $this->get($url)->assertOk();
                $dashboard->assertSee('href="'.$url.'"', escape: false);
            } else {
                $this->get($url)->assertForbidden();
                $dashboard->assertDontSee($url, escape: false);
            }
        }
    }

    public function test_guests_and_users_without_permissions_cannot_access_admin(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirectToRoute('login');
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        $this->getJson(route('admin.pengguna-role.index'))->assertForbidden();
        $this->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_direct_permissions_and_revocation_control_routes_without_role_checks(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo(['dashboard.view', 'ads.view']);
        $this->actingAs($user)->get(route('admin.iklan.index'))->assertOk();

        $user->revokePermissionTo('ads.view');

        $this->get(route('admin.iklan.index'))->assertForbidden();
    }

    /** @return array<string, array{string, list<string>, list<string>}> */
    public static function featurePermissions(): array
    {
        return [
            'writer scope' => ['Penulis', [
                'articles.create', 'articles.view-own', 'articles.update-own-draft', 'articles.delete-own-draft',
                'articles.submit', 'articles.assign-categories', 'articles.assign-tags',
                'media.view-own', 'media.upload', 'media.update-own', 'media.delete-own', 'seo.update-own-draft',
            ], [
                'articles.view-any', 'articles.update-any', 'articles.delete-any', 'articles.publish', 'articles.approve',
                'articles.reject', 'articles.schedule', 'articles.unpublish', 'articles.archive',
                'media.view-any', 'media.update-any', 'media.delete-any', 'categories.create', 'tags.delete',
                'seo.update-any', 'seo.set-indexing', 'users.create', 'roles.assign', 'ads.create', 'activity-log.view',
            ]],
            'editorial authority' => ['Editor', [
                'articles.view-any', 'articles.update-any', 'articles.approve', 'articles.reject', 'articles.request-revision',
                'articles.publish', 'articles.schedule', 'articles.unpublish', 'articles.archive',
                'articles.mark-breaking', 'articles.mark-advertorial', 'articles.view-history',
                'categories.create', 'categories.update', 'categories.delete', 'categories.reorder',
                'tags.create', 'tags.update', 'tags.delete', 'media.view-any', 'media.update-any', 'media.delete-any',
                'seo.update-any', 'seo.set-indexing', 'activity-log.view-own',
            ], ['articles.delete-any', 'users.view', 'roles.assign', 'roles.update-permissions', 'ads.create', 'settings.update', 'activity-log.view-any']],
            'advertising authority' => ['Ads Manager', [
                'ads.view', 'ads.create', 'ads.update', 'ads.delete', 'ads.schedule', 'ads.view-reports',
            ], ['articles.view', 'articles.create', 'articles.publish', 'categories.view', 'media.view', 'media.upload', 'seo.update-any', 'users.view', 'roles.assign', 'settings.view', 'activity-log.view']],
        ];
    }

    #[DataProvider('featurePermissions')]
    public function test_granular_permissions_respect_feature_and_ownership_boundaries(string $role, array $allowed, array $denied): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole($role);

        foreach ($allowed as $permission) {
            $this->assertTrue($user->can($permission), $role.' must allow '.$permission);
        }
        foreach ($denied as $permission) {
            $this->assertFalse($user->can($permission), $role.' must deny '.$permission);
        }
    }

    public function test_super_admin_has_every_registered_feature_permission_without_unknown_ability_bypass(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        foreach (Permission::pluck('name') as $permission) {
            $this->assertTrue($user->can($permission), $permission);
        }
        $this->assertFalse($user->can('undefined.ability'));
    }

    #[DataProvider('roleRoutes')]
    public function test_dashboard_limits_activity_and_user_data(string $role, array $allowedRoutes): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $other = User::factory()->create();
        $user->assignRole($role);
        activity()->causedBy($user)->log('Own editorial activity');
        activity()->causedBy($other)->log('Other staff activity');

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        if ($role === 'Super Admin') {
            $response->assertSee('Own editorial activity')->assertSee('Other staff activity')->assertSee('Total Pengguna');
        } elseif ($role === 'Editor') {
            $response->assertSee('Own editorial activity')->assertDontSee('Other staff activity')->assertDontSee('Total Pengguna');
        } else {
            $response->assertDontSee('Own editorial activity')->assertDontSee('Other staff activity')->assertDontSee('Total Pengguna');
        }
    }

    public function test_log_listing_permission_alone_does_not_expose_activity_records(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo(['dashboard.view', 'activity-log.view']);
        activity()->causedBy($user)->log('Sensitive activity');

        $this->actingAs($user)->get(route('admin.dashboard'))->assertDontSee('Sensitive activity');
    }
}
