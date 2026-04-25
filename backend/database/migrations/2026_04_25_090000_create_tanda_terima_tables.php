<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tanda_terima')) {
            Schema::create('tanda_terima', function (Blueprint $table): void {
                $table->id();
                $table->string('nomor_tanda_terima', 50)->unique();
                $table->string('nomor_surat_jalan', 50);
                $table->string('no_po', 50)->nullable();
                $table->date('tanggal');
                $table->foreignId('sppg_id')->nullable()->constrained('sppg')->nullOnDelete();
                $table->foreignId('armada_id')->nullable()->constrained('armada')->nullOnDelete();
                $table->foreignId('akuntan_id')->nullable()->constrained('karyawan')->nullOnDelete();
                $table->foreignId('driver_id')->nullable()->constrained('karyawan')->nullOnDelete();
                $table->string('status', 20)->default('draft');
                $table->timestamps();
            });
        } else {
            $this->repairTandaTerimaTable();
        }

        if (! Schema::hasTable('tanda_terima_items')) {
            Schema::create('tanda_terima_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tanda_terima_id')->constrained('tanda_terima')->cascadeOnDelete();
                $table->foreignId('penjualan_item_id')->nullable()->constrained('penjualan_items')->nullOnDelete();
                $table->string('nama_barang', 100);
                $table->decimal('qty', 15, 2);
                $table->string('satuan', 50)->nullable();
                $table->text('keterangan')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tanda_terima_items');
        Schema::dropIfExists('tanda_terima');
    }

    private function repairTandaTerimaTable(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `tanda_terima` MODIFY `sppg_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `tanda_terima` MODIFY `armada_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `tanda_terima` MODIFY `akuntan_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `tanda_terima` MODIFY `driver_id` BIGINT UNSIGNED NULL');

        $this->addForeignKeyIfMissing(
            'tanda_terima',
            'tanda_terima_sppg_id_foreign',
            fn (Blueprint $table) => $table->foreign('sppg_id')->references('id')->on('sppg')->nullOnDelete()
        );
        $this->addForeignKeyIfMissing(
            'tanda_terima',
            'tanda_terima_armada_id_foreign',
            fn (Blueprint $table) => $table->foreign('armada_id')->references('id')->on('armada')->nullOnDelete()
        );
        $this->addForeignKeyIfMissing(
            'tanda_terima',
            'tanda_terima_akuntan_id_foreign',
            fn (Blueprint $table) => $table->foreign('akuntan_id')->references('id')->on('karyawan')->nullOnDelete()
        );
        $this->addForeignKeyIfMissing(
            'tanda_terima',
            'tanda_terima_driver_id_foreign',
            fn (Blueprint $table) => $table->foreign('driver_id')->references('id')->on('karyawan')->nullOnDelete()
        );
    }

    private function addForeignKeyIfMissing(string $table, string $constraintName, callable $definition): void
    {
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraintName)
            ->exists();

        if ($exists) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($definition): void {
            $definition($blueprint);
        });
    }
};
