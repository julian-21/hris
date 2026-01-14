<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ========================================
        // CREATE PERMISSIONS
        // ========================================
        $permissions = [
            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',
            
            // Role Management
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            
            // Attendance
            'view attendance',
            'create attendance',
            'edit attendance',
            'delete attendance',
            'approve attendance',
            
            // Leave
            'view leave',
            'create leave',
            'edit leave',
            'delete leave',
            'approve leave',
            
            // Overtime
            'view overtime',
            'create overtime',
            'edit overtime',
            'delete overtime',
            'approve overtime',
            
            // Reports
            'view reports',
            'export reports',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // ========================================
        // CREATE ROLES & ASSIGN PERMISSIONS
        // ========================================
        
        // 1. ADMIN
        $admin = Role::create([
            'name' => 'Admin',
            'guard_name' => 'web'
        ]);
        $admin->givePermissionTo(Permission::all());

        // 2. SALES
        $sales = Role::create(['name' => 'Sales']);
        $sales->givePermissionTo([
            'view attendance', 'create attendance',
            'view leave', 'create leave',
            'view overtime', 'create overtime',
        ]);

        // 3. PPIC
        $ppic = Role::create(['name' => 'PPIC']);
        $ppic->givePermissionTo([
            'view attendance', 'create attendance',
            'view leave', 'create leave',
            'view overtime', 'create overtime',
        ]);

        // 4. PRODUKSI
        $produksi = Role::create(['name' => 'Produksi']);
        $produksi->givePermissionTo([
            'view attendance', 'create attendance',
            'view leave', 'create leave',
            'view overtime', 'create overtime',
        ]);

        // 5. HR
        $hr = Role::create(['name' => 'HR']);
        $hr->givePermissionTo([
            'view users', 'create users', 'edit users',
            'view attendance', 'approve attendance',
            'view leave', 'approve leave',
            'view overtime', 'approve overtime',
            'view reports', 'export reports',
        ]);

        // 6. FINANCE
        $finance = Role::create(['name' => 'Finance']);
        $finance->givePermissionTo([
            'view attendance', 'create attendance',
            'view leave', 'create leave',
            'view overtime', 'create overtime',
            'view reports',
        ]);

        // 7. KARYAWAN
        $karyawan = Role::create(['name' => 'Karyawan']);
        $karyawan->givePermissionTo([
            'view attendance', 'create attendance',
            'view leave', 'create leave',
            'view overtime', 'create overtime',
        ]);

        // 23. DIGITAL LEAD
        $digitalLead = Role::create(['name' => 'Digital Lead']);
        $digitalLead->givePermissionTo([
            'view attendance', 'create attendance', 'approve attendance',
            'view leave', 'create leave', 'approve leave',
            'view overtime', 'create overtime',
            'view reports',
        ]);

        // 24. DIGITAL PUBLIC RELATION
        $dpr = Role::create(['name' => 'Digital Public Relation']);
        $dpr->givePermissionTo([
            'view attendance', 'create attendance',
            'view leave', 'create leave',
            'view overtime', 'create overtime',
        ]);

        // 25. BISDEV
        $bisdev = Role::create(['name' => 'Bisdev']);
        $bisdev->givePermissionTo([
            'view attendance', 'create attendance',
            'view leave', 'create leave',
            'view overtime', 'create overtime',
        ]);

        // 26. SOSMED SPECIALIS
        $sosmed = Role::create(['name' => 'Sosmed Specialis']);
        $sosmed->givePermissionTo([
            'view attendance', 'create attendance',
            'view leave', 'create leave',
            'view overtime', 'create overtime',
        ]);

        // 27. ENGINEER
        $engineer = Role::create(['name' => 'Engineer']);
        $engineer->givePermissionTo([
            'view attendance', 'create attendance',
            'view leave', 'create leave',
            'view overtime', 'create overtime',
        ]);

        // 28. DIREKTUR
        $direktur = Role::create(['name' => 'Direktur']);
        $direktur->givePermissionTo(Permission::all());

        // 29. CSO
        $cso = Role::create(['name' => 'CSO']);
        $cso->givePermissionTo([
            'view attendance', 'create attendance',
            'view leave', 'create leave',
            'view overtime', 'create overtime',
        ]);

        // 30. PC
        $pc = Role::create(['name' => 'PC']);
        $pc->givePermissionTo([
            'view attendance', 'create attendance',
            'view leave', 'create leave',
            'view overtime', 'create overtime',
        ]);

        // 31. CANVASSING AGENT
        $canvassing = Role::create(['name' => 'Canvassing Agent']);
        $canvassing->givePermissionTo([
            'view attendance', 'create attendance',
            'view leave', 'create leave',
            'view overtime', 'create overtime',
        ]);
    }
}