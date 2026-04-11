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
        if (Schema::hasTable('bank_rekening')) {
            return;
        }

        Schema::create('bank_rekening', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_bank', 100)->nullable();
            $table->string('no_rek', 50)->nullable();
            $table->string('atas_nama', 100)->nullable();
            $table->string('cabang', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_rekening');
    }
};
