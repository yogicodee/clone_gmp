<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoice_penjualan', 'sppg_id')) {
            Schema::table('invoice_penjualan', function (Blueprint $table): void {
                $table->unsignedBigInteger('sppg_id')->nullable()->after('penjualan_id');
            });
        } else {
            DB::statement('ALTER TABLE invoice_penjualan MODIFY sppg_id BIGINT UNSIGNED NULL');
        }

        Schema::table('invoice_penjualan', function (Blueprint $table): void {
            $table->foreign('sppg_id')->references('id')->on('sppg')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_penjualan', function (Blueprint $table): void {
            if (Schema::hasColumn('invoice_penjualan', 'sppg_id')) {
                $table->dropForeign(['sppg_id']);
                $table->dropColumn('sppg_id');
            }
        });
    }
};
