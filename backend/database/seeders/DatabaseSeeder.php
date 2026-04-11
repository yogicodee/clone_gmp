<?php

namespace Database\Seeders;

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
        $this->upsertUser(
            'admin.demo@gmp.local',
            'Admin Demo',
            'admin'
        );

        $this->upsertUser(
            'superadmin.demo@gmp.local',
            'Super Admin Demo',
            'super_admin'
        );
    }

    private function upsertUser(string $email, string $nama, string $role): void
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

        User::query()->updateOrCreate(
            ['email' => $email],
            $payload
        );
    }
}
