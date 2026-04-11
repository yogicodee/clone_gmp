<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'nama')) {
                $table->string('nama', 100)->nullable()->after('id');
            }

            if (! Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'super_admin'])->nullable()->after('password');
            }

            if (! Schema::hasColumn('users', 'api_token')) {
                $table->string('api_token', 64)->nullable()->unique()->after('role');
            }
        });

        if (Schema::hasColumn('users', 'name') && Schema::hasColumn('users', 'nama')) {
            DB::statement("UPDATE users SET nama = COALESCE(NULLIF(nama, ''), name)");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'api_token')) {
                $table->dropUnique(['api_token']);
                $table->dropColumn('api_token');
            }

            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }

            if (Schema::hasColumn('users', 'nama')) {
                $table->dropColumn('nama');
            }
        });
    }
};
