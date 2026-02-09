<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Organization
            'organization.view',
            'organization.update',
            'organization.delete',

            // Members
            'members.view',
            'members.remove',
            'members.update-role',

            // Invitations
            'invitations.view',
            'invitations.create',
            'invitations.delete',

            // Projects
            'projects.view',
            'projects.create',
            'projects.update',
            'projects.delete',

            // Tasks
            'tasks.view',
            'tasks.create',
            'tasks.update',
            'tasks.delete',
            'tasks.assign',

            // Comments
            'comments.view',
            'comments.create',
            'comments.update',
            'comments.delete',

            // Attachments
            'attachments.view',
            'attachments.create',
            'attachments.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Admin: all permissions
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions($permissions);

        // Manager: projects, tasks, invitations management, no org/member management
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $manager->syncPermissions([
            'organization.view',
            'members.view',
            'invitations.view',
            'invitations.create',
            'invitations.delete',
            'projects.view',
            'projects.create',
            'projects.update',
            'projects.delete',
            'tasks.view',
            'tasks.create',
            'tasks.update',
            'tasks.delete',
            'tasks.assign',
            'comments.view',
            'comments.create',
            'comments.update',
            'comments.delete',
            'attachments.view',
            'attachments.create',
            'attachments.delete',
        ]);

        // User: basic CRUD on tasks/comments/attachments, read projects
        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $user->syncPermissions([
            'organization.view',
            'members.view',
            'projects.view',
            'tasks.view',
            'tasks.create',
            'tasks.update',
            'comments.view',
            'comments.create',
            'comments.update',
            'comments.delete',
            'attachments.view',
            'attachments.create',
            'attachments.delete',
        ]);
    }
}
