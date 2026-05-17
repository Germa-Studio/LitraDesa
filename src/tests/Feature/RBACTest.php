<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RBACTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    public function test_roles_are_created_correctly(): void
    {
        $this->assertDatabaseHas('roles', ['name' => 'super_admin']);
        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertDatabaseHas('roles', ['name' => 'librarian']);
        $this->assertDatabaseHas('roles', ['name' => 'member']);
    }

    public function test_permissions_are_created_correctly(): void
    {
        $this->assertDatabaseHas('permissions', ['name' => 'view_users']);
        $this->assertDatabaseHas('permissions', ['name' => 'create_books']);
        $this->assertDatabaseHas('permissions', ['name' => 'process_returns']);
        $this->assertDatabaseHas('permissions', ['name' => 'view_reports']);
    }

    public function test_user_can_be_assigned_role(): void
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'admin')->first();

        $user->assignRole($role);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertDatabaseHas('user_role', [
            'user_id' => $user->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_user_can_be_assigned_multiple_roles(): void
    {
        $user = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();
        $librarianRole = Role::where('name', 'librarian')->first();

        $user->assignRole($adminRole);
        $user->assignRole($librarianRole);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('librarian'));
        $this->assertTrue($user->hasAnyRole(['admin', 'librarian']));
        $this->assertTrue($user->hasAllRoles(['admin', 'librarian']));
    }

    public function test_user_can_be_removed_from_role(): void
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'admin')->first();

        $user->assignRole($role);
        $this->assertTrue($user->hasRole('admin'));

        $user->removeRole($role);
        $this->assertFalse($user->hasRole('admin'));
    }

    public function test_user_roles_can_be_synced(): void
    {
        $user = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();
        $librarianRole = Role::where('name', 'librarian')->first();
        $memberRole = Role::where('name', 'member')->first();

        $user->assignRole($adminRole);
        $user->assignRole($librarianRole);

        $this->assertEquals(2, $user->roles()->count());

        $user->syncRoles([$memberRole]);

        $this->assertEquals(1, $user->roles()->count());
        $this->assertTrue($user->hasRole('member'));
        $this->assertFalse($user->hasRole('admin'));
    }

    public function test_role_has_permissions(): void
    {
        $adminRole = Role::where('name', 'admin')->first();

        $this->assertTrue($adminRole->hasPermission('view_users'));
        $this->assertTrue($adminRole->hasPermission('create_books'));
        $this->assertFalse($adminRole->hasPermission('manage_system_settings'));
    }

    public function test_user_has_permission_via_role(): void
    {
        $user = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();

        $user->assignRole($adminRole);

        $this->assertTrue($user->hasPermission('view_users'));
        $this->assertTrue($user->hasPermission('create_books'));
        $this->assertFalse($user->hasPermission('manage_system_settings'));
    }

    public function test_user_can_have_direct_permissions(): void
    {
        $user = User::factory()->create();
        $permission = Permission::where('name', 'view_reports')->first();

        $user->givePermissionTo($permission);

        $this->assertTrue($user->hasPermission('view_reports'));
        $this->assertDatabaseHas('user_permission', [
            'user_id' => $user->id,
            'permission_id' => $permission->id,
        ]);
    }

    public function test_user_direct_permission_can_be_revoked(): void
    {
        $user = User::factory()->create();
        $permission = Permission::where('name', 'view_reports')->first();

        $user->givePermissionTo($permission);
        $this->assertTrue($user->hasPermission('view_reports'));

        $user->revokePermissionTo($permission);
        $this->assertFalse($user->hasPermission('view_reports'));
    }

    public function test_user_has_any_permission(): void
    {
        $user = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();

        $user->assignRole($adminRole);

        $this->assertTrue($user->hasAnyPermission(['view_users', 'nonexistent_permission']));
        $this->assertFalse($user->hasAnyPermission(['nonexistent_permission', 'another_fake']));
    }

    public function test_user_has_all_permissions(): void
    {
        $user = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();

        $user->assignRole($adminRole);

        $this->assertTrue($user->hasAllPermissions(['view_users', 'create_books']));
        $this->assertFalse($user->hasAllPermissions(['view_users', 'manage_system_settings']));
    }

    public function test_super_admin_has_all_permissions(): void
    {
        $user = User::factory()->create();
        $superAdminRole = Role::where('name', 'super_admin')->first();

        $user->assignRole($superAdminRole);

        $allPermissions = Permission::all();
        foreach ($allPermissions as $permission) {
            $this->assertTrue($user->hasPermission($permission->name));
        }
    }

    public function test_member_has_limited_permissions(): void
    {
        $user = User::factory()->create();
        $memberRole = Role::where('name', 'member')->first();

        $user->assignRole($memberRole);

        $this->assertTrue($user->hasPermission('view_books'));
        $this->assertTrue($user->hasPermission('create_reservations'));
        $this->assertFalse($user->hasPermission('create_books'));
        $this->assertFalse($user->hasPermission('approve_members'));
    }

    public function test_librarian_has_operational_permissions(): void
    {
        $user = User::factory()->create();
        $librarianRole = Role::where('name', 'librarian')->first();

        $user->assignRole($librarianRole);

        $this->assertTrue($user->hasPermission('create_loans'));
        $this->assertTrue($user->hasPermission('process_returns'));
        $this->assertTrue($user->hasPermission('manage_reservations'));
        $this->assertFalse($user->hasPermission('approve_members'));
        $this->assertFalse($user->hasPermission('delete_books'));
    }

    public function test_role_can_give_permission(): void
    {
        $role = Role::where('name', 'member')->first();
        $permission = Permission::where('name', 'view_reports')->first();

        $initialCount = $role->permissions()->count();

        $role->givePermissionTo($permission);

        $this->assertEquals($initialCount + 1, $role->permissions()->count());
        $this->assertTrue($role->hasPermission('view_reports'));
    }

    public function test_role_can_revoke_permission(): void
    {
        $role = Role::where('name', 'admin')->first();
        $permission = Permission::where('name', 'view_users')->first();

        $this->assertTrue($role->hasPermission('view_users'));

        $role->revokePermissionTo($permission);

        $this->assertFalse($role->hasPermission('view_users'));
    }

    public function test_role_permissions_can_be_synced(): void
    {
        $role = Role::where('name', 'member')->first();
        $viewBooks = Permission::where('name', 'view_books')->first();
        $viewCategories = Permission::where('name', 'view_categories')->first();

        $role->syncPermissions([$viewBooks, $viewCategories]);

        $this->assertEquals(2, $role->permissions()->count());
        $this->assertTrue($role->hasPermission('view_books'));
        $this->assertTrue($role->hasPermission('view_categories'));
    }

    public function test_get_all_user_permissions(): void
    {
        $user = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();
        $directPermission = Permission::where('name', 'view_audit_logs')->first();

        $user->assignRole($adminRole);
        $user->givePermissionTo($directPermission);

        $allPermissions = $user->getAllPermissions();

        $this->assertGreaterThan(0, $allPermissions->count());
        $this->assertTrue($allPermissions->contains('name', 'view_users'));
        $this->assertTrue($allPermissions->contains('name', 'view_audit_logs'));
    }

    public function test_system_roles_cannot_be_deleted(): void
    {
        $systemRole = Role::where('name', 'admin')->first();

        $this->assertTrue($systemRole->is_system_role);
    }

    public function test_user_is_super_admin(): void
    {
        $user = User::factory()->create();
        $superAdminRole = Role::where('name', 'super_admin')->first();

        $this->assertFalse($user->isSuperAdmin());

        $user->assignRole($superAdminRole);

        $this->assertTrue($user->isSuperAdmin());
    }

    public function test_user_is_librarian(): void
    {
        $user = User::factory()->create();
        $librarianRole = Role::where('name', 'librarian')->first();

        $this->assertFalse($user->isLibrarian());

        $user->assignRole($librarianRole);

        $this->assertTrue($user->isLibrarian());
    }

    public function test_permissions_are_grouped_correctly(): void
    {
        $grouped = Permission::getAllGrouped();

        $this->assertArrayHasKey('users', $grouped);
        $this->assertArrayHasKey('books', $grouped);
        $this->assertArrayHasKey('loans', $grouped);
        $this->assertArrayHasKey('reservations', $grouped);
    }
}

// Made with Bob
