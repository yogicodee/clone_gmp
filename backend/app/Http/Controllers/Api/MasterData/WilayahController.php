<?php

namespace App\Http\Controllers\Api\MasterData;

use App\Http\Controllers\Controller;
use App\Models\MasterData\Wilayah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WilayahController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'nama', 'alamat'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'nama';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;

        $wilayah = Wilayah::query()
            ->when($search, function ($query, string $keyword) {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('nama', 'like', '%'.$keyword.'%')
                        ->orWhere('alamat', 'like', '%'.$keyword.'%');
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data wilayah berhasil diambil.',
            'data' => $wilayah->items(),
            'meta' => [
                'current_page' => $wilayah->currentPage(),
                'last_page' => $wilayah->lastPage(),
                'per_page' => $wilayah->perPage(),
                'total' => $wilayah->total(),
                'from' => $wilayah->firstItem(),
                'to' => $wilayah->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatePayload($request);

        $wilayah = Wilayah::query()->create($payload);

        return response()->json([
            'message' => 'Wilayah berhasil ditambahkan.',
            'data' => $wilayah,
        ], 201);
    }

    public function show(Wilayah $wilayah): JsonResponse
    {
        return response()->json([
            'message' => 'Detail wilayah berhasil diambil.',
            'data' => $wilayah,
        ]);
    }

    public function update(Request $request, Wilayah $wilayah): JsonResponse
    {
        $payload = $this->validatePayload($request);

        $wilayah->update($payload);

        return response()->json([
            'message' => 'Wilayah berhasil diperbarui.',
            'data' => $wilayah->fresh(),
        ]);
    }

    public function destroy(Wilayah $wilayah): JsonResponse
    {
        $wilayah->delete();

        return response()->json([
            'message' => 'Wilayah berhasil dihapus.',
        ]);
    }

    /**
     * @return array{nama: string, alamat: string}
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'alamat' => ['required', 'string'],
        ]);
    }
}
