<?php

namespace App\Http\Controllers\Api\WarehouseSystem\WarehouseRetur;

use App\Http\Controllers\Controller;
use App\Support\CacheInvalidation;
use App\Models\WarehouseSystem\WarehouseRetur;
use App\Models\WarehouseSystem\WarehouseStokBasah;
use App\Models\WarehouseSystem\WarehouseStokKering;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WarehouseReturController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'gudang_id', 'jenis_stok', 'nama_barang', 'qty_retur', 'satuan_terkecil', 'harga_beli', 'alasan'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'nama_barang';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;

        $records = WarehouseRetur::query()
            ->with('gudang')
            ->when($search, function ($query, string $keyword): void {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('nama_barang', 'like', '%'.$keyword.'%')
                        ->orWhere('alasan', 'like', '%'.$keyword.'%')
                        ->orWhereHas('gudang', fn ($gudangQuery) => $gudangQuery->where('nama_gudang', 'like', '%'.$keyword.'%'));
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data retur/rusak berhasil diambil.',
            'data' => $records->items(),
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
        $payload = $this->validatePayload($request);

        $record = DB::transaction(function () use ($payload): WarehouseRetur {
            $this->deductStock($payload);

            return WarehouseRetur::query()->create($payload);
        });
        CacheInvalidation::flushStockCaches();

        return response()->json([
            'message' => 'Data retur/rusak berhasil ditambahkan.',
            'data' => $record->load('gudang'),
        ], 201);
    }

    public function show(WarehouseRetur $returRusak): JsonResponse
    {
        return response()->json([
            'message' => 'Detail retur/rusak berhasil diambil.',
            'data' => $returRusak->load('gudang'),
        ]);
    }

    public function update(Request $request, WarehouseRetur $returRusak): JsonResponse
    {
        $payload = $this->validatePayload($request);

        DB::transaction(function () use ($returRusak, $payload): void {
            $this->restoreStock($returRusak);
            $this->deductStock($payload);
            $returRusak->update($payload);
        });
        CacheInvalidation::flushStockCaches();

        return response()->json([
            'message' => 'Data retur/rusak berhasil diperbarui.',
            'data' => $returRusak->fresh()->load('gudang'),
        ]);
    }

    public function destroy(WarehouseRetur $returRusak): JsonResponse
    {
        DB::transaction(function () use ($returRusak): void {
            $this->restoreStock($returRusak);
            $returRusak->delete();
        });
        CacheInvalidation::flushStockCaches();

        return response()->json([
            'message' => 'Data retur/rusak berhasil dihapus.',
        ]);
    }

    /**
     * @return array{gudang_id:int,jenis_stok:string,nama_barang:string,qty_retur:numeric-string|float|int,satuan_terkecil:string,harga_beli:numeric-string|float|int,alasan:string}
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'gudang_id' => ['required', 'integer', 'exists:gudang,id'],
            'jenis_stok' => ['required', Rule::in(['kering', 'basah'])],
            'nama_barang' => ['required', 'string', 'max:100'],
            'qty_retur' => ['required', 'numeric', 'gt:0'],
            'satuan_terkecil' => ['required', 'string', 'max:50'],
            'harga_beli' => ['required', 'numeric', 'min:0'],
            'alasan' => ['required', 'string', 'max:255'],
        ]);
    }

    /**
     * @param  array{gudang_id:int,jenis_stok:string,nama_barang:string,qty_retur:numeric-string|float|int,satuan_terkecil:string,harga_beli:numeric-string|float|int}  $payload
     */
    private function deductStock(array $payload): void
    {
        $stockModel = $this->resolveStockModel($payload['jenis_stok']);
        $remaining = (float) $payload['qty_retur'];

        $stockRows = $stockModel::query()
            ->where('gudang_id', $payload['gudang_id'])
            ->where('nama_barang', $payload['nama_barang'])
            ->where('satuan_terkecil', $payload['satuan_terkecil'])
            ->where('harga_beli', $payload['harga_beli'])
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $available = (float) $stockRows->sum('qty');

        if ($available < $remaining) {
            throw ValidationException::withMessages([
                'qty_retur' => ['Stok tidak mencukupi untuk retur/rusak pada gudang dan jenis stok yang dipilih.'],
            ]);
        }

        foreach ($stockRows as $stockRow) {
            if ($remaining <= 0) {
                break;
            }

            $currentQty = (float) $stockRow->qty;
            $deductedQty = min($remaining, $currentQty);
            $stockRow->update([
                'qty' => $currentQty - $deductedQty,
            ]);

            $remaining -= $deductedQty;
        }
    }

    private function restoreStock(WarehouseRetur $returRusak): void
    {
        $stockModel = $this->resolveStockModel($returRusak->jenis_stok);

        $stockRow = $stockModel::query()
            ->where('gudang_id', $returRusak->gudang_id)
            ->where('nama_barang', $returRusak->nama_barang)
            ->where('satuan_terkecil', $returRusak->satuan_terkecil)
            ->where('harga_beli', $returRusak->harga_beli)
            ->orderBy('id')
            ->lockForUpdate()
            ->first();

        if ($stockRow) {
            $stockRow->update([
                'qty' => (float) $stockRow->qty + (float) $returRusak->qty_retur,
            ]);

            return;
        }

        $stockModel::query()->create([
            'gudang_id' => $returRusak->gudang_id,
            'nama_barang' => $returRusak->nama_barang,
            'qty' => $returRusak->qty_retur,
            'satuan_terkecil' => $returRusak->satuan_terkecil,
            'harga_beli' => $returRusak->harga_beli,
        ]);
    }

    /**
     * @return class-string<WarehouseStokKering>|class-string<WarehouseStokBasah>
     */
    private function resolveStockModel(string $jenisStok): string
    {
        return $jenisStok === 'kering'
            ? WarehouseStokKering::class
            : WarehouseStokBasah::class;
    }
}
