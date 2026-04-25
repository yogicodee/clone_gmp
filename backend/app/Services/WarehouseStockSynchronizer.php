<?php

namespace App\Services;

use App\Models\WarehouseSystem\WarehouseInbound;
use App\Models\WarehouseSystem\WarehouseStokBasah;
use App\Models\WarehouseSystem\WarehouseStokKering;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WarehouseStockSynchronizer
{
    public function sync(): void
    {
        $this->syncByKategori('kering', WarehouseStokKering::class);
        $this->syncByKategori('basah', WarehouseStokBasah::class);
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $modelClass
     */
    private function syncByKategori(string $kategori, string $modelClass): void
    {
        /** @var Collection<int, WarehouseInbound> $inbounds */
        $inbounds = WarehouseInbound::query()
            ->where('kategori', $kategori)
            ->orderBy('tanggal_masuk')
            ->orderBy('id')
            ->get([
                'id',
                'nama_barang',
                'tanggal_masuk',
                'qty',
                'satuan',
                'harga_satuan',
            ]);

        $now = Carbon::now();

        $rows = $inbounds
            ->groupBy(function (WarehouseInbound $inbound): string {
                return mb_strtolower($inbound->nama_barang.'|'.$inbound->satuan);
            })
            ->map(function (Collection $items) use ($now): array {
                /** @var WarehouseInbound $latest */
                $latest = $items->sortByDesc(function (WarehouseInbound $inbound): string {
                    $tanggalMasuk = $inbound->tanggal_masuk instanceof Carbon
                        ? $inbound->tanggal_masuk->format('Y-m-d')
                        : (string) $inbound->tanggal_masuk;

                    return $tanggalMasuk.'-'.$inbound->id;
                })->first();

                return [
                    'nama_barang' => $latest->nama_barang,
                    'qty' => $items->sum(static fn (WarehouseInbound $inbound): float => (float) $inbound->qty),
                    'satuan_terkecil' => $latest->satuan,
                    'harga_beli' => $latest->harga_satuan,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->values()
            ->all();

        DB::transaction(function () use ($modelClass, $rows): void {
            $modelClass::query()->delete();

            if ($rows !== []) {
                $modelClass::query()->insert($rows);
            }
        });
    }
}
