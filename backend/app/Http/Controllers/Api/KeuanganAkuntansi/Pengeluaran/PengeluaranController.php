<?php

namespace App\Http\Controllers\Api\KeuanganAkuntansi\Pengeluaran;

use App\Http\Controllers\Controller;
use App\Models\KeuanganAkuntansi\Pengeluaran;
use App\Support\CacheInvalidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PengeluaranController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'nama_operasional', 'tanggal_keluar', 'qty', 'satuan', 'harga_satuan'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'tanggal_keluar';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 10;

        $records = Pengeluaran::query()
            ->when($search, function ($query, string $keyword): void {
                $query->where('nama_operasional', 'like', '%'.$keyword.'%');
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data pengeluaran berhasil diambil.',
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
        $record = Pengeluaran::query()->create($this->validatePayload($request));
        CacheInvalidation::flushDashboardSummary();
        CacheInvalidation::flushLabaRugiTransaksional();

        return response()->json([
            'message' => 'Data pengeluaran berhasil ditambahkan.',
            'data' => $record,
        ], 201);
    }

    public function show(Pengeluaran $pengeluaran): JsonResponse
    {
        return response()->json([
            'message' => 'Detail pengeluaran berhasil diambil.',
            'data' => $pengeluaran,
        ]);
    }

    public function update(Request $request, Pengeluaran $pengeluaran): JsonResponse
    {
        $pengeluaran->update($this->validatePayload($request));
        CacheInvalidation::flushDashboardSummary();
        CacheInvalidation::flushLabaRugiTransaksional();

        return response()->json([
            'message' => 'Data pengeluaran berhasil diperbarui.',
            'data' => $pengeluaran->fresh(),
        ]);
    }

    public function destroy(Pengeluaran $pengeluaran): JsonResponse
    {
        $pengeluaran->delete();
        CacheInvalidation::flushDashboardSummary();
        CacheInvalidation::flushLabaRugiTransaksional();

        return response()->json([
            'message' => 'Data pengeluaran berhasil dihapus.',
        ]);
    }

    /**
     * @return array{nama_operasional: string, tanggal_keluar: string, qty: numeric-string|int|float, satuan: string, harga_satuan: numeric-string|int|float}
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'nama_operasional' => ['required', 'string', 'max:100'],
            'tanggal_keluar' => ['required', 'date'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'satuan' => ['required', 'string', 'max:50'],
            'harga_satuan' => ['required', 'numeric', 'gt:0'],
        ], [
            'qty.gt' => 'Qty harus lebih besar dari 0.',
            'harga_satuan.gt' => 'Harga harus lebih besar dari 0.',
        ]);
    }
}
