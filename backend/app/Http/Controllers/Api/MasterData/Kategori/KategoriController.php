<?php

namespace App\Http\Controllers\Api\MasterData\Kategori;

use App\Http\Controllers\Controller;
use App\Models\MasterData\Kategori;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KategoriController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'kode', 'nama_satuan'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'kode';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;

        $kategori = Kategori::query()
            ->when($search, function ($query, string $keyword) {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('kode', 'like', '%'.$keyword.'%')
                        ->orWhere('nama_satuan', 'like', '%'.$keyword.'%');
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data kategori berhasil diambil.',
            'data' => $kategori->items(),
            'meta' => [
                'current_page' => $kategori->currentPage(),
                'last_page' => $kategori->lastPage(),
                'per_page' => $kategori->perPage(),
                'total' => $kategori->total(),
                'from' => $kategori->firstItem(),
                'to' => $kategori->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatePayload($request);

        $kategori = Kategori::query()->create($payload);

        return response()->json([
            'message' => 'Kategori berhasil ditambahkan.',
            'data' => $kategori,
        ], 201);
    }

    public function show(Kategori $kategori): JsonResponse
    {
        return response()->json([
            'message' => 'Detail kategori berhasil diambil.',
            'data' => $kategori,
        ]);
    }

    public function update(Request $request, Kategori $kategori): JsonResponse
    {
        $payload = $this->validatePayload($request, $kategori);

        $kategori->update($payload);

        return response()->json([
            'message' => 'Kategori berhasil diperbarui.',
            'data' => $kategori->fresh(),
        ]);
    }

    public function destroy(Kategori $kategori): JsonResponse
    {
        $kategori->delete();

        return response()->json([
            'message' => 'Kategori berhasil dihapus.',
        ]);
    }

    /**
     * @return array{kode: string, nama_satuan: string}
     */
    private function validatePayload(Request $request, ?Kategori $kategori = null): array
    {
        $payload = $request->validate([
            'kode' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('kategori', 'kode')->ignore($kategori?->id),
            ],
            'nama_satuan' => ['required', 'string', 'max:100'],
        ], [
            'kode.regex' => 'Kode hanya boleh berisi huruf kapital, angka, dan tanda minus (-).',
            'kode.unique' => 'Kode sudah digunakan.',
        ]);

        $normalizedNamaSatuan = mb_strtolower(trim($payload['nama_satuan']));

        $duplicateExists = Kategori::query()
            ->when($kategori !== null, fn ($query) => $query->whereKeyNot($kategori->id))
            ->whereRaw('LOWER(TRIM(nama_satuan)) = ?', [$normalizedNamaSatuan])
            ->exists();

        if ($duplicateExists) {
            abort(response()->json([
                'message' => 'Nama satuan sudah digunakan.',
                'errors' => [
                    'nama_satuan' => ['Nama satuan sudah digunakan.'],
                ],
            ], 422));
        }

        return $payload;
    }
}
