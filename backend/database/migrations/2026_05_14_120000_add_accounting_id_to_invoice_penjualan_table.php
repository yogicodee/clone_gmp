<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoice_penjualan', 'accounting_id')) {
            Schema::table('invoice_penjualan', function (Blueprint $table): void {
                $table->foreignId('accounting_id')
                    ->nullable()
                    ->after('sppg_id')
                    ->constrained('karyawan')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoice_penjualan', 'accounting_id')) {
            Schema::table('invoice_penjualan', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('accounting_id');
            });
        }
    }
};
