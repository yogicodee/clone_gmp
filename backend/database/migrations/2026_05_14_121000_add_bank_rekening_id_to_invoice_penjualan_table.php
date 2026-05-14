<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoice_penjualan', 'bank_rekening_id')) {
            Schema::table('invoice_penjualan', function (Blueprint $table): void {
                $table->foreignId('bank_rekening_id')
                    ->nullable()
                    ->after('accounting_id')
                    ->constrained('bank_rekening')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoice_penjualan', 'bank_rekening_id')) {
            Schema::table('invoice_penjualan', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('bank_rekening_id');
            });
        }
    }
};
