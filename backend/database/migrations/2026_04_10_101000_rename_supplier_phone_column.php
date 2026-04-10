<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('supplier')) {
            return;
        }

        if (! Schema::hasColumn('supplier', 'no_telepon') || Schema::hasColumn('supplier', 'no_telp')) {
            // Skip when column was already renamed.
        } else {
            Schema::table('supplier', function (Blueprint $table): void {
                $table->renameColumn('no_telepon', 'no_telp');
            });
        }

        if (! Schema::hasColumn('supplier', 'kategori_supplier') || Schema::hasColumn('supplier', 'kategori')) {
            return;
        }

        Schema::table('supplier', function (Blueprint $table): void {
            $table->renameColumn('kategori_supplier', 'kategori');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('supplier')) {
            return;
        }

        if (! Schema::hasColumn('supplier', 'no_telp') || Schema::hasColumn('supplier', 'no_telepon')) {
            // Skip when column was already rolled back.
        } else {
            Schema::table('supplier', function (Blueprint $table): void {
                $table->renameColumn('no_telp', 'no_telepon');
            });
        }

        if (! Schema::hasColumn('supplier', 'kategori') || Schema::hasColumn('supplier', 'kategori_supplier')) {
            return;
        }

        Schema::table('supplier', function (Blueprint $table): void {
            $table->renameColumn('kategori', 'kategori_supplier');
        });
    }
};
