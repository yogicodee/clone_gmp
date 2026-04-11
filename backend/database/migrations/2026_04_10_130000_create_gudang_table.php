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
        if (Schema::hasTable('gudang')) {
            return;
        }

        Schema::create('gudang', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_gudang', 100)->nullable();
            $table->text('alamat')->nullable();
            $table->string('nama_pic', 100)->nullable();
            $table->string('no_pic', 20)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gudang');
    }
};
