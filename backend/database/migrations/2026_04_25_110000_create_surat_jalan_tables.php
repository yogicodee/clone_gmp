<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('surat_jalan')) {
            Schema::create('surat_jalan', function (Blueprint $table): void {
                $table->id();
                $table->string('nomor_surat_jalan', 50)->unique();
                $table->string('no_po', 50)->nullable();
                $table->date('tanggal');
                $table->foreignId('sppg_id')->nullable()->constrained('sppg')->nullOnDelete();
                $table->foreignId('armada_id')->nullable()->constrained('armada')->nullOnDelete();
                $table->foreignId('driver_id')->nullable()->constrained('karyawan')->nullOnDelete();
                $table->string('status', 20)->default('draft');
                $table->timestamps();
            });
        } else {
            $this->repairSuratJalanTable();
        }

        if (! Schema::hasTable('surat_jalan_items')) {
            Schema::create('surat_jalan_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('surat_jalan_id')->constrained('surat_jalan')->cascadeOnDelete();
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
        Schema::dropIfExists('surat_jalan_items');
        Schema::dropIfExists('surat_jalan');
    }

    private function repairSuratJalanTable(): void
    {
        Schema::table('surat_jalan', function (Blueprint $table): void {
            if (! Schema::hasColumn('surat_jalan', 'nomor_surat_jalan')) {
                $table->string('nomor_surat_jalan', 50)->nullable()->after('id');
            }

            if (! Schema::hasColumn('surat_jalan', 'no_po')) {
                $table->string('no_po', 50)->nullable()->after('nomor_surat_jalan');
            }

            if (! Schema::hasColumn('surat_jalan', 'tanggal')) {
                $table->date('tanggal')->nullable()->after('no_po');
            }

            if (! Schema::hasColumn('surat_jalan', 'sppg_id')) {
                $table->foreignId('sppg_id')->nullable()->after('tanggal');
            }

            if (! Schema::hasColumn('surat_jalan', 'armada_id')) {
                $table->foreignId('armada_id')->nullable()->after('sppg_id');
            }

            if (! Schema::hasColumn('surat_jalan', 'driver_id')) {
                $table->foreignId('driver_id')->nullable()->after('armada_id');
            }

            if (! Schema::hasColumn('surat_jalan', 'status')) {
                $table->string('status', 20)->default('draft')->after('driver_id');
            }
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `surat_jalan` MODIFY `nomor_surat_jalan` VARCHAR(50) NULL');
            DB::statement('ALTER TABLE `surat_jalan` MODIFY `no_po` VARCHAR(50) NULL');
            DB::statement('ALTER TABLE `surat_jalan` MODIFY `tanggal` DATE NULL');
            DB::statement('ALTER TABLE `surat_jalan` MODIFY `sppg_id` BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE `surat_jalan` MODIFY `armada_id` BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE `surat_jalan` MODIFY `driver_id` BIGINT UNSIGNED NULL');
            DB::statement("UPDATE `surat_jalan` SET `status` = COALESCE(NULLIF(`status`, ''), 'draft')");
            DB::statement('ALTER TABLE `surat_jalan` MODIFY `status` VARCHAR(20) NOT NULL DEFAULT \'draft\'');
        }

        $this->addUniqueIfMissing('surat_jalan', 'surat_jalan_nomor_surat_jalan_unique', ['nomor_surat_jalan']);
        $this->addForeignKeyIfMissing(
            'surat_jalan',
            'surat_jalan_sppg_id_foreign',
            fn (Blueprint $table) => $table->foreign('sppg_id')->references('id')->on('sppg')->nullOnDelete()
        );
        $this->addForeignKeyIfMissing(
            'surat_jalan',
            'surat_jalan_armada_id_foreign',
            fn (Blueprint $table) => $table->foreign('armada_id')->references('id')->on('armada')->nullOnDelete()
        );
        $this->addForeignKeyIfMissing(
            'surat_jalan',
            'surat_jalan_driver_id_foreign',
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

    private function addUniqueIfMissing(string $table, string $indexName, array $columns): void
    {
        $exists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $indexName)
            ->exists();

        if ($exists) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName): void {
            $blueprint->unique($columns, $indexName);
        });
    }
};
