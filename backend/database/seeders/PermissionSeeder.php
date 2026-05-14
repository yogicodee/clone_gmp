<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $permissions = [
            ['code' => 'dashboard.view', 'name' => 'Lihat Dashboard', 'group_name' => 'Dashboard'],
            ['code' => 'master.view', 'name' => 'Lihat Master Data', 'group_name' => 'Master Data'],
            ['code' => 'master.manage', 'name' => 'Kelola Master Data', 'group_name' => 'Master Data'],
            ['code' => 'pembelian.view', 'name' => 'Lihat Transaksi Pembelian', 'group_name' => 'Transaksi Pembelian'],
            ['code' => 'pembelian.manage', 'name' => 'Kelola Transaksi Pembelian', 'group_name' => 'Transaksi Pembelian'],
            ['code' => 'warehouse.view', 'name' => 'Lihat Warehouse', 'group_name' => 'Warehouse'],
            ['code' => 'warehouse.manage', 'name' => 'Kelola Warehouse', 'group_name' => 'Warehouse'],
            ['code' => 'penjualan.view', 'name' => 'Lihat Transaksi Penjualan', 'group_name' => 'Transaksi Penjualan'],
            ['code' => 'penjualan.manage', 'name' => 'Kelola Transaksi Penjualan', 'group_name' => 'Transaksi Penjualan'],
            ['code' => 'keuangan.view', 'name' => 'Lihat Keuangan & Akuntansi', 'group_name' => 'Keuangan & Akuntansi'],
            ['code' => 'keuangan.manage', 'name' => 'Kelola Keuangan & Akuntansi', 'group_name' => 'Keuangan & Akuntansi'],
            ['code' => 'laporan.view', 'name' => 'Lihat Laporan & Analisa', 'group_name' => 'Laporan & Analisa'],
            ['code' => 'users.view', 'name' => 'Lihat Pengguna', 'group_name' => 'Pengguna'],
            ['code' => 'users.manage', 'name' => 'Kelola Pengguna', 'group_name' => 'Pengguna'],
            ['code' => 'export.pdf', 'name' => 'Export PDF', 'group_name' => 'Aksi Tambahan'],
            ['code' => 'delete.data', 'name' => 'Hapus Data', 'group_name' => 'Aksi Tambahan'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['code' => $permission['code']],
                [
                    'name' => $permission['name'],
                    'group_name' => $permission['group_name'],
                    'description' => $permission['name'],
                ]
            );
        }
    }
}
