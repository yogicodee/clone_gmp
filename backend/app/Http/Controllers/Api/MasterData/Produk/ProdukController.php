<?php

namespace App\Http\Controllers\Api\MasterData\Produk;

use App\Http\Controllers\Controller;
use App\Models\MasterData\Produk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProdukController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'sku', 'nama', 'kategori', 'satuan'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'nama';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;

        $produk = Produk::query()
            ->when($search, function ($query, string $keyword) {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('sku', 'like', '%'.$keyword.'%')
                        ->orWhere('nama', 'like', '%'.$keyword.'%')
                        ->orWhere('kategori', 'like', '%'.$keyword.'%');
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data produk berhasil diambil.',
            'data' => $produk->items(),
            'meta' => [
                'current_page' => $produk->currentPage(),
                'last_page' => $produk->lastPage(),
                'per_page' => $produk->perPage(),
                'total' => $produk->total(),
                'from' => $produk->firstItem(),
                'to' => $produk->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatePayload($request);

        $produk = Produk::query()->create($payload);

        return response()->json([
            'message' => 'Produk berhasil ditambahkan.',
            'data' => $produk,
        ], 201);
    }

    public function show(Produk $produk): JsonResponse
    {
        return response()->json([
            'message' => 'Detail produk berhasil diambil.',
            'data' => $produk,
        ]);
    }

    public function update(Request $request, Produk $produk): JsonResponse
    {
        $payload = $this->validatePayload($request, $produk);

        $produk->update($payload);

        return response()->json([
            'message' => 'Produk berhasil diperbarui.',
            'data' => $produk->fresh(),
        ]);
    }

    public function destroy(Produk $produk): JsonResponse
    {
        $produk->delete();

        return response()->json([
            'message' => 'Produk berhasil dihapus.',
        ]);
    }

    /**
     * @return array{sku: string, nama: string, kategori: string, satuan: string}
     */
    private function validatePayload(Request $request, ?Produk $produk = null): array
    {
        $payload = $request->validate([
            'sku' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('produk', 'sku')->ignore($produk?->id),
            ],
            'nama' => ['required', 'string', 'max:100'],
            'kategori' => ['required', 'string', 'max:50'],
            'satuan' => ['required', 'string', 'max:50'],
        ], [
            'sku.regex' => 'SKU hanya boleh berisi huruf kapital, angka, dan tanda minus (-).',
            'sku.unique' => 'SKU sudah digunakan.',
        ]);

        $normalizedNama = mb_strtolower(trim($payload['nama']));
        $normalizedKategori = mb_strtolower(trim($payload['kategori']));
        $normalizedSatuan = mb_strtolower(trim($payload['satuan']));

        $duplicateExists = Produk::query()
            ->when($produk !== null, fn ($query) => $query->whereKeyNot($produk->id))
            ->whereRaw('LOWER(TRIM(nama)) = ?', [$normalizedNama])
            ->whereRaw('LOWER(TRIM(kategori)) = ?', [$normalizedKategori])
            ->whereRaw('LOWER(TRIM(satuan)) = ?', [$normalizedSatuan])
            ->exists();

        if ($duplicateExists) {
            abort(response()->json([
                'message' => 'Produk dengan nama, kategori, dan satuan yang sama sudah ada.',
                'errors' => [
                    'nama' => ['Produk dengan nama, kategori, dan satuan yang sama sudah ada.'],
                ],
            ], 422));
        }

        return $payload;
    }
}
