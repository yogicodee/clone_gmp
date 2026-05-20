<?php

namespace App\Http\Controllers\Api\MasterData\Supplier;

use App\Http\Controllers\Controller;
use App\Models\MasterData\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'nama', 'alamat', 'no_telp', 'kategori'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'nama';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;

        $suppliers = Supplier::query()
            ->when($search, function ($query, string $keyword) {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('nama', 'like', '%'.$keyword.'%')
                        ->orWhere('alamat', 'like', '%'.$keyword.'%')
                        ->orWhere('no_telp', 'like', '%'.$keyword.'%')
                        ->orWhere('kategori', 'like', '%'.$keyword.'%');
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data supplier berhasil diambil.',
            'data' => array_map(
                fn (Supplier $supplier): array => $this->transformSupplier($supplier),
                $suppliers->items()
            ),
            'meta' => [
                'current_page' => $suppliers->currentPage(),
                'last_page' => $suppliers->lastPage(),
                'per_page' => $suppliers->perPage(),
                'total' => $suppliers->total(),
                'from' => $suppliers->firstItem(),
                'to' => $suppliers->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatePayload($request);

        $supplier = Supplier::query()->create($payload);

        return response()->json([
            'message' => 'Supplier berhasil ditambahkan.',
            'data' => $this->transformSupplier($supplier),
        ], 201);
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return response()->json([
            'message' => 'Detail supplier berhasil diambil.',
            'data' => $this->transformSupplier($supplier),
        ]);
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $payload = $this->validatePayload($request, $supplier);

        $supplier->update($payload);

        return response()->json([
            'message' => 'Supplier berhasil diperbarui.',
            'data' => $this->transformSupplier($supplier->fresh()),
        ]);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();

        return response()->json([
            'message' => 'Supplier berhasil dihapus.',
        ]);
    }

    /**
     * @return array{nama: string, alamat: string, no_telp: string, kategori: string}
     */
    private function validatePayload(Request $request, ?Supplier $ignoreSupplier = null): array
    {
        $payload = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'alamat' => ['required', 'string'],
            'no_telp' => ['required', 'string', 'min:10', 'max:20', 'regex:/^([0-9\\s\\-\\+\\(\\)]*)$/'],
            'kategori' => ['required', 'string', 'max:50'],
        ], [
            'no_telp.regex' => 'No telepon hanya boleh berisi angka dan karakter khusus tertentu.',
            'no_telp.min' => 'No telepon minimal 10 karakter.',
            'no_telp.max' => 'No telepon maksimal 20 karakter.',
        ]);

        $normalizedNama = mb_strtolower(trim($payload['nama']));
        $normalizedAlamat = mb_strtolower(trim($payload['alamat']));
        $normalizedTelepon = $this->normalizePhone($payload['no_telp']);

        $duplicateExists = Supplier::query()
            ->when($ignoreSupplier !== null, fn ($query) => $query->whereKeyNot($ignoreSupplier->id))
            ->whereRaw('LOWER(TRIM(nama)) = ?', [$normalizedNama])
            ->whereRaw('LOWER(TRIM(alamat)) = ?', [$normalizedAlamat])
            ->get()
            ->contains(function (Supplier $supplier) use ($normalizedTelepon): bool {
                return $this->normalizePhone((string) $supplier->no_telp) === $normalizedTelepon;
            });

        if ($duplicateExists) {
            abort(response()->json([
                'message' => 'Supplier dengan nama, alamat, dan no telepon yang sama sudah ada.',
                'errors' => [
                    'nama' => ['Supplier dengan nama, alamat, dan no telepon yang sama sudah ada.'],
                ],
            ], 422));
        }

        return $payload;
    }

    private function normalizePhone(string $value): string
    {
        return preg_replace('/\D+/', '', trim($value)) ?? '';
    }

    /**
     * @return array{id: int, nama: string|null, alamat: string|null, no_telp: string|null, kategori: string|null}
     */
    private function transformSupplier(Supplier $supplier): array
    {
        return [
            'id' => $supplier->id,
            'nama' => $supplier->nama,
            'alamat' => $supplier->alamat,
            'no_telp' => $supplier->no_telp,
            'kategori' => $supplier->kategori,
        ];
    }
}
