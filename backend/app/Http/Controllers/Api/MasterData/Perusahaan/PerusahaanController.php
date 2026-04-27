<?php

namespace App\Http\Controllers\Api\MasterData\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\MasterData\Perusahaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PerusahaanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'nama_perusahaan', 'alamat', 'nama_pic'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'nama_perusahaan';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;

        $perusahaan = Perusahaan::query()
            ->when($search, function ($query, string $keyword) {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('nama_perusahaan', 'like', '%'.$keyword.'%')
                        ->orWhere('alamat', 'like', '%'.$keyword.'%')
                        ->orWhere('nama_pic', 'like', '%'.$keyword.'%');
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data perusahaan berhasil diambil.',
            'data' => $perusahaan->items(),
            'meta' => [
                'current_page' => $perusahaan->currentPage(),
                'last_page' => $perusahaan->lastPage(),
                'per_page' => $perusahaan->perPage(),
                'total' => $perusahaan->total(),
                'from' => $perusahaan->firstItem(),
                'to' => $perusahaan->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatePayload($request);

        $perusahaan = Perusahaan::query()->create($payload);

        return response()->json([
            'message' => 'Perusahaan berhasil ditambahkan.',
            'data' => $perusahaan,
        ], 201);
    }

    public function show(Perusahaan $perusahaan): JsonResponse
    {
        return response()->json([
            'message' => 'Detail perusahaan berhasil diambil.',
            'data' => $perusahaan,
        ]);
    }

    public function update(Request $request, Perusahaan $perusahaan): JsonResponse
    {
        $payload = $this->validatePayload($request);

        $perusahaan->update($payload);

        return response()->json([
            'message' => 'Perusahaan berhasil diperbarui.',
            'data' => $perusahaan->fresh(),
        ]);
    }

    public function destroy(Perusahaan $perusahaan): JsonResponse
    {
        $perusahaan->delete();

        return response()->json([
            'message' => 'Perusahaan berhasil dihapus.',
        ]);
    }

    /**
     * @return array{nama_perusahaan: string, alamat: string, nama_pic: string}
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'nama_perusahaan' => ['required', 'string', 'max:100'],
            'alamat' => ['required', 'string'],
            'nama_pic' => ['required', 'string', 'max:100'],
        ]);
    }
}
