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
        if (Schema::hasTable('sppg')) {
            return;
        }

        Schema::create('sppg', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_sppg', 100)->nullable();
            $table->text('alamat')->nullable();
            $table->string('nama_yayasan', 100)->nullable();
            $table->string('nama_penanggungjawab', 100)->nullable();
            $table->string('no_penanggungjawab', 20)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sppg');
    }
};
