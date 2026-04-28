<?php

namespace App\Http\Controllers\Api\LaporanAnalisa\LaporanStokBarang;

use App\Http\Controllers\Controller;
use App\Models\WarehouseSystem\WarehouseStokBasah;
use App\Models\WarehouseSystem\WarehouseStokKering;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class LaporanStokBarangController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'jenis_stok' => ['nullable', Rule::in(['kering', 'basah'])],
            'sort_field' => ['nullable', Rule::in(['nama_barang', 'nama_gudang', 'qty', 'satuan_terkecil', 'harga_beli', 'jenis_stok', 'nilai_stok'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $search = strtolower($filters['search'] ?? '');
        $jenisStok = $filters['jenis_stok'] ?? null;
        $sortField = $filters['sort_field'] ?? 'nama_barang';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;
        $page = $filters['page'] ?? 1;

        $records = $this->collectStockRows($jenisStok)
            ->when($search !== '', function (Collection $rows) use ($search): Collection {
                return $rows->filter(function (array $row) use ($search): bool {
                    return str_contains(strtolower($row['nama_barang']), $search)
                        || str_contains(strtolower($row['nama_gudang'] ?? ''), $search);
                })->values();
            });

        $records = $this->sortRows($records, $sortField, $sortOrder);

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
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    private function collectStockRows(?string $jenisStok): Collection
    {
        $rows = collect();

        if ($jenisStok === null || $jenisStok === 'kering') {
            $rows = $rows->concat(
                WarehouseStokKering::query()
                    ->with('gudang')
                    ->get()
                    ->map(fn (WarehouseStokKering $record): array => $this->transformRow($record, 'kering'))
            );
        }

        if ($jenisStok === null || $jenisStok === 'basah') {
            $rows = $rows->concat(
                WarehouseStokBasah::query()
                    ->with('gudang')
                    ->get()
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
            'nilai_stok' => $qty * $hargaBeli,
        ];
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
