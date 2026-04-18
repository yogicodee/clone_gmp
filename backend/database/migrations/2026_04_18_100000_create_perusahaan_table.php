<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('perusahaan')) {
            return;
        }

        Schema::create('perusahaan', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_perusahaan', 100);
            $table->text('alamat');
            $table->string('nama_pic', 100);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perusahaan');
    }
};
