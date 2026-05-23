<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('perusahaan')) {
            return;
        }

        if (! Schema::hasColumn('perusahaan', 'tema_invoice')) {
            Schema::table('perusahaan', function (Blueprint $table): void {
                $table->string('tema_invoice', 50)->default('theme_01')->after('logo_path');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('perusahaan')) {
            return;
        }

        if (Schema::hasColumn('perusahaan', 'tema_invoice')) {
            Schema::table('perusahaan', function (Blueprint $table): void {
                $table->dropColumn('tema_invoice');
            });
        }
    }
};

