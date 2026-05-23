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

        if (! Schema::hasColumn('perusahaan', 'logo_path')) {
            Schema::table('perusahaan', function (Blueprint $table): void {
                $table->string('logo_path')->nullable()->after('nama_pic');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('perusahaan')) {
            return;
        }

        if (Schema::hasColumn('perusahaan', 'logo_path')) {
            Schema::table('perusahaan', function (Blueprint $table): void {
                $table->dropColumn('logo_path');
            });
        }
    }
};

