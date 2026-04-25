<?php

namespace App\Http\Controllers\Api\TransaksiPenjualan;

use App\Http\Controllers\Controller;
use App\Models\TransaksiPenjualan\Penjualan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PenjualanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'kode_penjualan', 'tanggal', 'total_harga', 'status'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'tanggal';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 10;

        $records = Penjualan::query()
            ->when($search, function ($query, string $keyword): void {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('kode_penjualan', 'like', '%'.$keyword.'%')
                        ->orWhere('status', 'like', '%'.$keyword.'%');
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data penjualan berhasil diambil.',
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
        $payload['total_harga'] = 0;

        $record = Penjualan::query()->create($payload);

        return response()->json([
            'message' => 'Data penjualan berhasil ditambahkan.',
            'data' => $record,
        ], 201);
    }

    public function show(Penjualan $penjualan): JsonResponse
    {
        $penjualan->load(['items.gudang', 'items.orderPenawaranItem.orderPenawaran']);

        return response()->json([
            'message' => 'Detail penjualan berhasil diambil.',
            'data' => $penjualan,
        ]);
    }

    public function update(Request $request, Penjualan $penjualan): JsonResponse
    {
        $payload = $this->validatePayload($request, $penjualan);
        $penjualan->update($payload);

        return response()->json([
            'message' => 'Data penjualan berhasil diperbarui.',
            'data' => $penjualan->fresh(),
        ]);
    }

    public function destroy(Penjualan $penjualan): JsonResponse
    {
        $penjualan->delete();

        return response()->json([
            'message' => 'Data penjualan berhasil dihapus.',
        ]);
    }

    private function validatePayload(Request $request, ?Penjualan $penjualan = null): array
    {
        return $request->validate([
            'kode_penjualan' => [
                'required',
                'string',
                'max:50',
                Rule::unique('penjualan', 'kode_penjualan')->ignore($penjualan?->id),
            ],
            'tanggal' => ['required', 'date'],
            'status' => ['required', Rule::in(['draft', 'selesai', 'batal'])],
        ]);
    }
}
