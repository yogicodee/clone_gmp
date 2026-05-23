<?php

namespace App\Http\Controllers\Api\LaporanAnalisa\LaporanStokBarang;

use App\Http\Controllers\Controller;
use App\Support\CacheInvalidation;
use App\Models\WarehouseSystem\WarehouseStokBasah;
use App\Models\WarehouseSystem\WarehouseStokKering;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class LaporanStokBarangController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'gudang_id' => ['nullable', 'integer', 'exists:gudang,id'],
            'jenis_stok' => ['nullable', Rule::in(['kering', 'basah'])],
            'periode' => ['nullable', Rule::in(['harian', 'mingguan', 'bulanan', 'tahunan'])],
            'tanggal' => ['nullable', 'date'],
            'sort_field' => ['nullable', Rule::in(['id', 'nama_barang', 'nama_gudang', 'qty', 'satuan_terkecil', 'harga_beli', 'jenis_stok', 'nilai_stok', 'tanggal_masuk'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $search = strtolower($filters['search'] ?? '');
        $gudangId = $filters['gudang_id'] ?? null;
        $jenisStok = $filters['jenis_stok'] ?? null;
        $periode = $filters['periode'] ?? 'harian';
        $tanggalAcuan = Carbon::parse($filters['tanggal'] ?? now()->toDateString());
        $sortField = $filters['sort_field'] ?? 'nama_barang';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;
        $page = $filters['page'] ?? 1;

        $cacheKey = sprintf(
            'laporan_stok_barang:%s:%s:%s:%s:%s:%s',
            $search !== '' ? md5($search) : 'all',
            $gudangId ?? 'all',
            $jenisStok ?? 'all',
            $periode,
            $tanggalAcuan->toDateString(),
            $sortField.'-'.$sortOrder
        );

        $cached = Cache::tags([CacheInvalidation::TAG_LAPORAN_STOK_BARANG])->remember($cacheKey, now()->addMinutes(5), function () use ($jenisStok, $gudangId, $periode, $tanggalAcuan, $search, $sortField, $sortOrder): array {
            $records = $this->collectStockRows($jenisStok, $gudangId, $periode, $tanggalAcuan)
                ->when($search !== '', function (Collection $rows) use ($search): Collection {
                    return $rows->filter(function (array $row) use ($search): bool {
                        return str_contains(strtolower($row['nama_barang']), $search)
                            || str_contains(strtolower($row['nama_gudang'] ?? ''), $search)
                            || str_contains(strtolower($row['jenis_stok'] ?? ''), $search);
                    })->values();
                });

            $records = $this->sortRows($records, $sortField, $sortOrder)->values();

            return [
                'records' => $records->all(),
                'summary' => [
                    'total_qty' => round($records->sum('qty'), 2),
                    'total_nilai_stok' => round($records->sum('nilai_stok'), 2),
                    'per_gudang' => $this->groupRowsByGudang($records),
                ],
            ];
        });

        $records = collect($cached['records']);

        $paginator = new LengthAwarePaginator(
            $records->forPage($page, $perPage)->values(),
            $records->count(),
            $perPage,
            $page
        );

        return response()->json([
            'message' => 'Laporan stok barang berhasil diambil.',
            'data' => $paginator->items(),
            'meta' => [
                'periode' => $periode,
                'tanggal_acuan' => $tanggalAcuan->toDateString(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'summary' => [
                'total_qty' => $cached['summary']['total_qty'],
                'total_nilai_stok' => $cached['summary']['total_nilai_stok'],
                'per_gudang' => $cached['summary']['per_gudang'],
            ],
        ]);
    }

    private function collectStockRows(?string $jenisStok, ?int $gudangId, string $periode, Carbon $tanggalAcuan): Collection
    {
        $rows = collect();

        if ($jenisStok === null || $jenisStok === 'kering') {
            $rows = $rows->concat(
                WarehouseStokKering::query()
                    ->with(['gudang', 'inbound'])
                    ->when($gudangId !== null, fn ($query) => $query->where('gudang_id', $gudangId))
                    ->get()
                    ->filter(fn (WarehouseStokKering $record): bool => $this->matchesPeriode($record->inbound?->tanggal_masuk, $periode, $tanggalAcuan))
                    ->map(fn (WarehouseStokKering $record): array => $this->transformRow($record, 'kering'))
            );
        }

        if ($jenisStok === null || $jenisStok === 'basah') {
            $rows = $rows->concat(
                WarehouseStokBasah::query()
                    ->with(['gudang', 'inbound'])
                    ->when($gudangId !== null, fn ($query) => $query->where('gudang_id', $gudangId))
                    ->get()
                    ->filter(fn (WarehouseStokBasah $record): bool => $this->matchesPeriode($record->inbound?->tanggal_masuk, $periode, $tanggalAcuan))
                    ->map(fn (WarehouseStokBasah $record): array => $this->transformRow($record, 'basah'))
            );
        }

        return $rows->values();
    }

    private function transformRow(object $record, string $jenisStok): array
    {
        $qty = (float) $record->qty;
        $hargaBeli = (float) $record->harga_beli;

        return [
            'id' => $record->id,
            'nama_barang' => $record->nama_barang,
            'nama_gudang' => $record->gudang?->nama_gudang,
            'qty' => $qty,
            'satuan_terkecil' => $record->satuan_terkecil,
            'harga_beli' => $hargaBeli,
            'jenis_stok' => $jenisStok,
            'tanggal_masuk' => $record->inbound?->tanggal_masuk?->format('Y-m-d'),
            'nilai_stok' => $qty * $hargaBeli,
        ];
    }

    private function matchesPeriode($tanggalMasuk, string $periode, Carbon $tanggalAcuan): bool
    {
        if ($tanggalMasuk === null) {
            return true;
        }

        $tanggal = Carbon::parse($tanggalMasuk);

        return match ($periode) {
            'harian' => $tanggal->isSameDay($tanggalAcuan),
            'mingguan' => $tanggal->betweenIncluded($tanggalAcuan->copy()->startOfWeek(), $tanggalAcuan->copy()->endOfWeek()),
            'bulanan' => $tanggal->year === $tanggalAcuan->year && $tanggal->month === $tanggalAcuan->month,
            'tahunan' => $tanggal->year === $tanggalAcuan->year,
            default => true,
        };
    }

    private function groupRowsByGudang(Collection $rows): array
    {
        return $rows
            ->groupBy(fn (array $row) => $row['nama_gudang'] ?? 'Tanpa Gudang')
            ->map(function (Collection $group, string $namaGudang): array {
                return [
                    'nama_gudang' => $namaGudang,
                    'total_qty' => round($group->sum('qty'), 2),
                    'total_nilai_stok' => round($group->sum('nilai_stok'), 2),
                    'items' => $group->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function sortRows(Collection $rows, string $sortField, string $sortOrder): Collection
    {
        $sorted = $rows->sort(function (array $left, array $right) use ($sortField, $sortOrder): int {
            $leftValue = $left[$sortField] ?? null;
            $rightValue = $right[$sortField] ?? null;

            if (is_string($leftValue) || is_string($rightValue)) {
                $comparison = strcmp((string) $leftValue, (string) $rightValue);
            } else {
                $comparison = ($leftValue <=> $rightValue);
            }

            return $sortOrder === 'desc' ? -$comparison : $comparison;
        });

        return $sorted->values();
    }
}
