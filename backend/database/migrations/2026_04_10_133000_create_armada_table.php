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
        if (Schema::hasTable('armada')) {
            return;
        }

        Schema::create('armada', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_unit', 100)->nullable();
            $table->string('no_pol', 20)->nullable();
            $table->enum('jenis_kendaraan', ['Roda 2', 'Roda 4'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('armada');
    }
};
