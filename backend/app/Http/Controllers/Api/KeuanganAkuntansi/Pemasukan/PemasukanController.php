<?php

namespace App\Http\Controllers\Api\KeuanganAkuntansi\Pemasukan;

use App\Http\Controllers\Controller;
use App\Models\KeuanganAkuntansi\Pemasukan;
use App\Support\CacheInvalidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PemasukanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'tanggal', 'jenis', 'jumlah', 'keterangan'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'tanggal';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 10;

        $records = Pemasukan::query()
            ->when($search, function ($query, string $keyword): void {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('jenis', 'like', '%'.$keyword.'%')
                        ->orWhere('keterangan', 'like', '%'.$keyword.'%');
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data pemasukan berhasil diambil.',
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
        $record = Pemasukan::query()->create($this->validatePayload($request));
        CacheInvalidation::flushLabaRugiTransaksional();

        return response()->json([
            'message' => 'Data pemasukan berhasil ditambahkan.',
            'data' => $record,
        ], 201);
    }

    public function show(Pemasukan $pemasukan): JsonResponse
    {
        return response()->json([
            'message' => 'Detail pemasukan berhasil diambil.',
            'data' => $pemasukan,
        ]);
    }

    public function update(Request $request, Pemasukan $pemasukan): JsonResponse
    {
        $pemasukan->update($this->validatePayload($request));
        CacheInvalidation::flushLabaRugiTransaksional();

        return response()->json([
            'message' => 'Data pemasukan berhasil diperbarui.',
            'data' => $pemasukan->fresh(),
        ]);
    }

    public function destroy(Pemasukan $pemasukan): JsonResponse
    {
        $pemasukan->delete();
        CacheInvalidation::flushLabaRugiTransaksional();

        return response()->json([
            'message' => 'Data pemasukan berhasil dihapus.',
        ]);
    }

    /**
     * @return array{tanggal: string, jenis: string, jumlah: numeric-string|int|float, keterangan: string}
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'tanggal' => ['required', 'date'],
            'jenis' => ['required', Rule::in(['modal', 'hutang'])],
            'jumlah' => ['required', 'numeric', 'gt:0'],
            'keterangan' => ['required', 'string', 'max:255'],
        ], [
            'jumlah.gt' => 'Jumlah pemasukan harus lebih besar dari 0.',
        ]);
    }
}
