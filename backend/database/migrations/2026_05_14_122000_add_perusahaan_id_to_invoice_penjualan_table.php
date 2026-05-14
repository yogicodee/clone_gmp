<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoice_penjualan', 'perusahaan_id')) {
            Schema::table('invoice_penjualan', function (Blueprint $table): void {
                $table->foreignId('perusahaan_id')
                    ->nullable()
                    ->after('bank_rekening_id')
                    ->constrained('perusahaan')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoice_penjualan', 'perusahaan_id')) {
            Schema::table('invoice_penjualan', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('perusahaan_id');
            });
        }
    }
};
