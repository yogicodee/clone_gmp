<?php

namespace App\Http\Controllers\Api\WarehouseSystem\WarehouseStokBasah;

use App\Http\Controllers\Controller;
use App\Models\WarehouseSystem\WarehouseStokBasah;
use App\Support\CacheInvalidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WarehouseStokBasahController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'nama_barang', 'gudang_id', 'qty', 'satuan_terkecil', 'harga_beli'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'nama_barang';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;

        $records = WarehouseStokBasah::query()
            ->leftJoin('gudang', 'gudang.id', '=', 'warehouse_stok_basah.gudang_id')
            ->selectRaw('
                MIN(warehouse_stok_basah.id) as id,
                warehouse_stok_basah.gudang_id,
                warehouse_stok_basah.nama_barang,
                SUM(warehouse_stok_basah.qty) as qty,
                warehouse_stok_basah.satuan_terkecil,
                COALESCE(
                    SUM(warehouse_stok_basah.qty * warehouse_stok_basah.harga_beli) / NULLIF(SUM(warehouse_stok_basah.qty), 0),
                    AVG(warehouse_stok_basah.harga_beli)
                ) as harga_beli,
                MAX(gudang.nama_gudang) as gudang_nama
            ')
            ->when($search, function ($query, string $keyword): void {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('warehouse_stok_basah.nama_barang', 'like', '%'.$keyword.'%')
                        ->orWhere('gudang.nama_gudang', 'like', '%'.$keyword.'%');
                });
            })
            ->groupBy(
                'warehouse_stok_basah.gudang_id',
                'warehouse_stok_basah.nama_barang',
                'warehouse_stok_basah.satuan_terkecil'
            )
            ->orderBy($this->resolveSortColumn($sortField), $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data stok basah berhasil diambil.',
            'data' => array_map(
                fn ($record): array => $this->transformRecord($record),
                $records->items()
            ),
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
                'from' => $records->firstItem(),
                'to' => $records->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $record = WarehouseStokBasah::query()->create($this->validatePayload($request));
        CacheInvalidation::flushStockCaches();

        return response()->json([
            'message' => 'Data stok basah berhasil ditambahkan.',
            'data' => $record->load('gudang'),
        ], 201);
    }

    public function show(WarehouseStokBasah $stokBasah): JsonResponse
    {
        return response()->json([
            'message' => 'Detail stok basah berhasil diambil.',
            'data' => $stokBasah->load('gudang'),
        ]);
    }

    public function update(Request $request, WarehouseStokBasah $stokBasah): JsonResponse
    {
        $stokBasah->update($this->validatePayload($request));
        CacheInvalidation::flushStockCaches();

        return response()->json([
            'message' => 'Data stok basah berhasil diperbarui.',
            'data' => $stokBasah->fresh()->load('gudang'),
        ]);
    }

    public function destroy(WarehouseStokBasah $stokBasah): JsonResponse
    {
        $stokBasah->delete();
        CacheInvalidation::flushStockCaches();

        return response()->json([
            'message' => 'Data stok basah berhasil dihapus.',
        ]);
    }

    /**
     * @return array{nama_barang:string,qty:numeric-string|float|int,satuan_terkecil:string,harga_beli:numeric-string|float|int}
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'gudang_id' => ['required', 'integer', 'exists:gudang,id'],
            'nama_barang' => ['required', 'string', 'max:100'],
            'qty' => ['required', 'numeric', 'min:0'],
            'satuan_terkecil' => ['required', 'string', 'max:50'],
            'harga_beli' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function resolveSortColumn(string $sortField): string
    {
        return match ($sortField) {
            'gudang_id' => 'gudang_nama',
            'qty' => 'qty',
            'satuan_terkecil' => 'warehouse_stok_basah.satuan_terkecil',
            'harga_beli' => 'harga_beli',
            default => 'warehouse_stok_basah.nama_barang',
        };
    }

    /**
     * @return array{
     *     id:int,
     *     gudang_id:int|null,
     *     nama_barang:string,
     *     qty:float,
     *     satuan_terkecil:string,
     *     harga_beli:float,
     *     gudang: array{id:int|null,nama_gudang:string|null}|null
     * }
     */
    private function transformRecord(object $record): array
    {
        return [
            'id' => (int) $record->id,
            'gudang_id' => $record->gudang_id !== null ? (int) $record->gudang_id : null,
            'nama_barang' => (string) $record->nama_barang,
            'qty' => (float) $record->qty,
            'satuan_terkecil' => (string) $record->satuan_terkecil,
            'harga_beli' => (float) $record->harga_beli,
            'gudang' => [
                'id' => $record->gudang_id !== null ? (int) $record->gudang_id : null,
                'nama_gudang' => $record->gudang_nama,
            ],
        ];
    }
}
