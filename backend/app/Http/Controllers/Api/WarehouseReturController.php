<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WarehouseRetur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WarehouseReturController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'nama_barang', 'qty_retur', 'satuan_terkecil', 'harga_beli', 'alasan'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'nama_barang';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;

        $records = WarehouseRetur::query()
            ->when($search, fn ($query, string $keyword) => $query->where('nama_barang', 'like', '%'.$keyword.'%'))
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
        $record = WarehouseRetur::query()->create($this->validatePayload($request));

        return response()->json([
            'message' => 'Data retur/rusak berhasil ditambahkan.',
            'data' => $record,
        ], 201);
    }

    public function show(WarehouseRetur $returRusak): JsonResponse
    {
        return response()->json([
            'message' => 'Detail retur/rusak berhasil diambil.',
            'data' => $returRusak,
        ]);
    }

    public function update(Request $request, WarehouseRetur $returRusak): JsonResponse
    {
        $returRusak->update($this->validatePayload($request));

        return response()->json([
            'message' => 'Data retur/rusak berhasil diperbarui.',
            'data' => $returRusak->fresh(),
        ]);
    }

    public function destroy(WarehouseRetur $returRusak): JsonResponse
    {
        $returRusak->delete();

        return response()->json([
            'message' => 'Data retur/rusak berhasil dihapus.',
        ]);
    }

    /**
     * @return array{nama_barang:string,qty_retur:numeric-string|float|int,satuan_terkecil:string,harga_beli:numeric-string|float|int,alasan:string}
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'nama_barang' => ['required', 'string', 'max:100'],
            'qty_retur' => ['required', 'numeric', 'gt:0'],
            'satuan_terkecil' => ['required', 'string', 'max:50'],
            'harga_beli' => ['required', 'numeric', 'min:0'],
            'alasan' => ['required', 'string', 'max:255'],
        ]);
    }
}
