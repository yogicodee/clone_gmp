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
        if (Schema::hasTable('karyawan')) {
            return;
        }

        Schema::create('karyawan', function (Blueprint $table): void {
            $table->id();
            $table->string('nama', 100)->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->string('jabatan', 50)->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('karyawan');
    }
};
