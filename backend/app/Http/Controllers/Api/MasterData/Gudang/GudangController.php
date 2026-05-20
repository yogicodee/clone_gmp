<?php

namespace App\Http\Controllers\Api\MasterData\Gudang;

use App\Http\Controllers\Controller;
use App\Models\MasterData\Gudang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GudangController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'nama_gudang', 'alamat', 'nama_pic', 'no_pic'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'nama_gudang';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;

        $gudang = Gudang::query()
            ->when($search, function ($query, string $keyword) {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('nama_gudang', 'like', '%'.$keyword.'%')
                        ->orWhere('alamat', 'like', '%'.$keyword.'%')
                        ->orWhere('nama_pic', 'like', '%'.$keyword.'%')
                        ->orWhere('no_pic', 'like', '%'.$keyword.'%');
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data gudang berhasil diambil.',
            'data' => $gudang->items(),
            'meta' => [
                'current_page' => $gudang->currentPage(),
                'last_page' => $gudang->lastPage(),
                'per_page' => $gudang->perPage(),
                'total' => $gudang->total(),
                'from' => $gudang->firstItem(),
                'to' => $gudang->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatePayload($request);

        $gudang = Gudang::query()->create($payload);

        return response()->json([
            'message' => 'Gudang berhasil ditambahkan.',
            'data' => $gudang,
        ], 201);
    }

    public function show(Gudang $gudang): JsonResponse
    {
        return response()->json([
            'message' => 'Detail gudang berhasil diambil.',
            'data' => $gudang,
        ]);
    }

    public function update(Request $request, Gudang $gudang): JsonResponse
    {
        $payload = $this->validatePayload($request, $gudang);

        $gudang->update($payload);

        return response()->json([
            'message' => 'Gudang berhasil diperbarui.',
            'data' => $gudang->fresh(),
        ]);
    }

    public function destroy(Gudang $gudang): JsonResponse
    {
        $gudang->delete();

        return response()->json([
            'message' => 'Gudang berhasil dihapus.',
        ]);
    }

    /**
     * @return array{nama_gudang: string, alamat: string, nama_pic: string, no_pic: string}
     */
    private function validatePayload(Request $request, ?Gudang $ignoreGudang = null): array
    {
        $payload = $request->validate([
            'nama_gudang' => ['required', 'string', 'max:100'],
            'alamat' => ['required', 'string'],
            'nama_pic' => ['required', 'string', 'max:100'],
            'no_pic' => ['required', 'string', 'min:10', 'max:20', 'regex:/^([0-9\\s\\-\\+\\(\\)]*)$/'],
        ], [
            'no_pic.regex' => 'No PIC hanya boleh berisi angka dan karakter khusus tertentu.',
            'no_pic.min' => 'No PIC minimal 10 karakter.',
            'no_pic.max' => 'No PIC maksimal 20 karakter.',
        ]);

        $normalizedNamaGudang = mb_strtolower(trim($payload['nama_gudang']));
        $normalizedAlamat = mb_strtolower(trim($payload['alamat']));
        $normalizedNamaPic = mb_strtolower(trim($payload['nama_pic']));
        $normalizedNoPic = $this->normalizePhone($payload['no_pic']);

        $duplicateExists = Gudang::query()
            ->when($ignoreGudang !== null, fn ($query) => $query->whereKeyNot($ignoreGudang->id))
            ->whereRaw('LOWER(TRIM(nama_gudang)) = ?', [$normalizedNamaGudang])
            ->whereRaw('LOWER(TRIM(alamat)) = ?', [$normalizedAlamat])
            ->whereRaw('LOWER(TRIM(nama_pic)) = ?', [$normalizedNamaPic])
            ->get()
            ->contains(function (Gudang $gudang) use ($normalizedNoPic): bool {
                return $this->normalizePhone((string) $gudang->no_pic) === $normalizedNoPic;
            });

        if ($duplicateExists) {
            abort(response()->json([
                'message' => 'Gudang dengan nama, alamat, PIC, dan no PIC yang sama sudah ada.',
                'errors' => [
                    'nama_gudang' => ['Gudang dengan nama, alamat, PIC, dan no PIC yang sama sudah ada.'],
                ],
            ], 422));
        }

        return $payload;
    }

    private function normalizePhone(string $value): string
    {
        return preg_replace('/\D+/', '', trim($value)) ?? '';
    }
}
