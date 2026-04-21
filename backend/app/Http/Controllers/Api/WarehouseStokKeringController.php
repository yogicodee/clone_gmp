<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WarehouseStokKering;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WarehouseStokKeringController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'nama_barang', 'qty', 'satuan_terkecil', 'harga_beli'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'nama_barang';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;

        $records = WarehouseStokKering::query()
            ->with('gudang')
            ->when($search, fn ($query, string $keyword) => $query->where('nama_barang', 'like', '%'.$keyword.'%'))
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data stok kering berhasil diambil.',
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
        $record = WarehouseStokKering::query()->create($this->validatePayload($request));

        return response()->json([
            'message' => 'Data stok kering berhasil ditambahkan.',
            'data' => $record->load('gudang'),
        ], 201);
    }

    public function show(WarehouseStokKering $stokKering): JsonResponse
    {
        return response()->json([
            'message' => 'Detail stok kering berhasil diambil.',
            'data' => $stokKering->load('gudang'),
        ]);
    }

    public function update(Request $request, WarehouseStokKering $stokKering): JsonResponse
    {
        $stokKering->update($this->validatePayload($request));

        return response()->json([
            'message' => 'Data stok kering berhasil diperbarui.',
            'data' => $stokKering->fresh()->load('gudang'),
        ]);
    }

    public function destroy(WarehouseStokKering $stokKering): JsonResponse
    {
        $stokKering->delete();

        return response()->json([
            'message' => 'Data stok kering berhasil dihapus.',
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
}
