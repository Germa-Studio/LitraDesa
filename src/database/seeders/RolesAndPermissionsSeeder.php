<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = $this->createPermissions();

        // Create roles
        $roles = $this->createRoles();

        // Assign permissions to roles
        $this->assignPermissionsToRoles($roles, $permissions);
    }

    /**
     * Create all permissions.
     */
    private function createPermissions(): array
    {
        $permissionsData = [
            // User Management
            ['name' => 'view_users', 'display_name' => 'View Users', 'description' => 'View user list and details', 'group' => 'users'],
            ['name' => 'create_users', 'display_name' => 'Create Users', 'description' => 'Create new users', 'group' => 'users'],
            ['name' => 'edit_users', 'display_name' => 'Edit Users', 'description' => 'Edit user information', 'group' => 'users'],
            ['name' => 'delete_users', 'display_name' => 'Delete Users', 'description' => 'Delete users', 'group' => 'users'],
            ['name' => 'approve_members', 'display_name' => 'Approve Members', 'description' => 'Approve or reject member registrations', 'group' => 'users'],
            ['name' => 'suspend_members', 'display_name' => 'Suspend Members', 'description' => 'Suspend or reactivate members', 'group' => 'users'],
            ['name' => 'manage_roles', 'display_name' => 'Manage Roles', 'description' => 'Create, edit, and delete roles', 'group' => 'users'],
            ['name' => 'assign_roles', 'display_name' => 'Assign Roles', 'description' => 'Assign roles to users', 'group' => 'users'],

            // Book Management
            ['name' => 'view_books', 'display_name' => 'View Books', 'description' => 'View book catalog', 'group' => 'books'],
            ['name' => 'create_books', 'display_name' => 'Create Books', 'description' => 'Add new books to catalog', 'group' => 'books'],
            ['name' => 'edit_books', 'display_name' => 'Edit Books', 'description' => 'Edit book information', 'group' => 'books'],
            ['name' => 'delete_books', 'display_name' => 'Delete Books', 'description' => 'Delete books from catalog', 'group' => 'books'],
            ['name' => 'manage_book_copies', 'display_name' => 'Manage Book Copies', 'description' => 'Add, edit, or remove book copies', 'group' => 'books'],
            ['name' => 'bulk_import_books', 'display_name' => 'Bulk Import Books', 'description' => 'Import books via CSV', 'group' => 'books'],

            // Category Management
            ['name' => 'view_categories', 'display_name' => 'View Categories', 'description' => 'View book categories', 'group' => 'categories'],
            ['name' => 'manage_categories', 'display_name' => 'Manage Categories', 'description' => 'Create, edit, and delete categories', 'group' => 'categories'],

            // Loan Management
            ['name' => 'view_loans', 'display_name' => 'View Loans', 'description' => 'View loan records', 'group' => 'loans'],
            ['name' => 'create_loans', 'display_name' => 'Create Loans', 'description' => 'Process book loans', 'group' => 'loans'],
            ['name' => 'process_returns', 'display_name' => 'Process Returns', 'description' => 'Process book returns', 'group' => 'loans'],
            ['name' => 'mark_books_lost', 'display_name' => 'Mark Books Lost', 'description' => 'Mark loaned books as lost', 'group' => 'loans'],
            ['name' => 'view_loan_history', 'display_name' => 'View Loan History', 'description' => 'View complete loan history', 'group' => 'loans'],
            ['name' => 'view_own_loans', 'display_name' => 'View Own Loans', 'description' => 'View own loan history', 'group' => 'loans'],

            // Reservation Management
            ['name' => 'view_reservations', 'display_name' => 'View Reservations', 'description' => 'View all reservations', 'group' => 'reservations'],
            ['name' => 'create_reservations', 'display_name' => 'Create Reservations', 'description' => 'Create book reservations', 'group' => 'reservations'],
            ['name' => 'cancel_reservations', 'display_name' => 'Cancel Reservations', 'description' => 'Cancel reservations', 'group' => 'reservations'],
            ['name' => 'manage_reservations', 'display_name' => 'Manage Reservations', 'description' => 'Mark reservations as ready or expired', 'group' => 'reservations'],
            ['name' => 'view_own_reservations', 'display_name' => 'View Own Reservations', 'description' => 'View own reservations', 'group' => 'reservations'],

            // Reports
            ['name' => 'view_reports', 'display_name' => 'View Reports', 'description' => 'View system reports', 'group' => 'reports'],
            ['name' => 'view_overdue_report', 'display_name' => 'View Overdue Report', 'description' => 'View overdue loans report', 'group' => 'reports'],
            ['name' => 'view_popular_books', 'display_name' => 'View Popular Books', 'description' => 'View popular books report', 'group' => 'reports'],
            ['name' => 'view_statistics', 'display_name' => 'View Statistics', 'description' => 'View system statistics', 'group' => 'reports'],

            // System Configuration
            ['name' => 'manage_system_settings', 'display_name' => 'Manage System Settings', 'description' => 'Configure system settings', 'group' => 'system'],
            ['name' => 'view_audit_logs', 'display_name' => 'View Audit Logs', 'description' => 'View system audit logs', 'group' => 'system'],
        ];

        $permissions = [];
        foreach ($permissionsData as $permissionData) {
            $permissions[$permissionData['name']] = Permission::create($permissionData);
        }

        return $permissions;
    }

    /**
     * Create all roles.
     */
    private function createRoles(): array
    {
        $rolesData = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Admin',
                'description' => 'Full system access with all permissions',
                'is_system_role' => true,
            ],
            [
                'name' => 'admin',
                'display_name' => 'Admin',
                'description' => 'Administrative access to manage users, books, and view reports',
                'is_system_role' => true,
            ],
            [
                'name' => 'librarian',
                'display_name' => 'Librarian',
                'description' => 'Process loans/returns, manage reservations, and basic inventory updates',
                'is_system_role' => true,
            ],
            [
                'name' => 'member',
                'display_name' => 'Member',
                'description' => 'Browse catalog, reserve/borrow books, and manage own profile',
                'is_system_role' => true,
            ],
        ];

        $roles = [];
        foreach ($rolesData as $roleData) {
            $roles[$roleData['name']] = Role::create($roleData);
        }

        return $roles;
    }

    /**
     * Assign permissions to roles.
     */
    private function assignPermissionsToRoles(array $roles, array $permissions): void
    {
        // Super Admin - All permissions
        $roles['super_admin']->syncPermissions(array_values($permissions));

        // Admin - Most permissions except system configuration
        $roles['admin']->syncPermissions([
            $permissions['view_users'],
            $permissions['create_users'],
            $permissions['edit_users'],
            $permissions['delete_users'],
            $permissions['approve_members'],
            $permissions['suspend_members'],
            $permissions['assign_roles'],
            $permissions['view_books'],
            $permissions['create_books'],
            $permissions['edit_books'],
            $permissions['delete_books'],
            $permissions['manage_book_copies'],
            $permissions['bulk_import_books'],
            $permissions['view_categories'],
            $permissions['manage_categories'],
            $permissions['view_loans'],
            $permissions['create_loans'],
            $permissions['process_returns'],
            $permissions['mark_books_lost'],
            $permissions['view_loan_history'],
            $permissions['view_reservations'],
            $permissions['manage_reservations'],
            $permissions['view_reports'],
            $permissions['view_overdue_report'],
            $permissions['view_popular_books'],
            $permissions['view_statistics'],
        ]);

        // Librarian - Operational permissions
        $roles['librarian']->syncPermissions([
            $permissions['view_users'],
            $permissions['view_books'],
            $permissions['edit_books'],
            $permissions['manage_book_copies'],
            $permissions['view_categories'],
            $permissions['view_loans'],
            $permissions['create_loans'],
            $permissions['process_returns'],
            $permissions['view_loan_history'],
            $permissions['view_reservations'],
            $permissions['manage_reservations'],
            $permissions['view_overdue_report'],
        ]);

        // Member - Basic user permissions
        $roles['member']->syncPermissions([
            $permissions['view_books'],
            $permissions['view_categories'],
            $permissions['view_own_loans'],
            $permissions['create_reservations'],
            $permissions['cancel_reservations'],
            $permissions['view_own_reservations'],
        ]);
    }
}

// Made with Bob
