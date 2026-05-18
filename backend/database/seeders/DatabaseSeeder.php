<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminUser = $this->upsertUser(
            'admin.demo@gmp.local',
            'Admin Demo',
            'admin'
        );

        $superAdminUser = $this->upsertUser(
            'superadmin.demo@gmp.local',
            'Super Admin Demo',
            'super_admin'
        );

        $this->call(PermissionSeeder::class);
        $this->syncDemoUserPermissions($adminUser, $superAdminUser);
        $this->call(DashboardSummarySeeder::class);
        $this->call(DashboardSalesBySppgSeeder::class);
        $this->call(LaporanStokBarangSeeder::class);
        $this->call(LabaRugiTransaksionalSeeder::class);
    }

    private function upsertUser(string $email, string $nama, string $role): User
    {
        $payload = [
            'email' => $email,
            'password' => 'rahasia123',
        ];

        if (Schema::hasColumn('users', 'nama')) {
            $payload['nama'] = $nama;
        }

        if (Schema::hasColumn('users', 'name')) {
            $payload['name'] = $nama;
        }

        if (Schema::hasColumn('users', 'role')) {
            $payload['role'] = $role;
        }

        return User::query()->updateOrCreate(
            ['email' => $email],
            $payload
        );
    }

    private function syncDemoUserPermissions(User $adminUser, User $superAdminUser): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('user_permissions')) {
            return;
        }

        $adminPermissionCodes = [
            'dashboard.view',
            'master.view',
            'master.manage',
            'pembelian.view',
            'pembelian.manage',
            'warehouse.view',
            'warehouse.manage',
            'penjualan.view',
            'penjualan.manage',
            'keuangan.view',
            'keuangan.manage',
            'laporan.view',
            'export.pdf',
            'delete.data',
        ];

        $adminPermissionIds = Permission::query()
            ->whereIn('code', $adminPermissionCodes)
            ->pluck('id')
            ->all();

        $adminUser->permissions()->sync($adminPermissionIds);

        if ($superAdminUser->normalizedRole() === 'superadmin') {
            $superAdminUser->permissions()->syncWithoutDetaching([]);
        }
    }
}
