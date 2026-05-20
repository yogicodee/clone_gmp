<?php

namespace App\Http\Controllers\Api\MasterData\Sppg;

use App\Http\Controllers\Controller;
use App\Models\MasterData\Sppg;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SppgController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in([
                'id',
                'nama_sppg',
                'alamat',
                'nama_yayasan',
                'nama_penanggungjawab',
                'no_penanggungjawab',
            ])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'nama_sppg';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;

        $sppg = Sppg::query()
            ->when($search, function ($query, string $keyword) {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('nama_sppg', 'like', '%'.$keyword.'%')
                        ->orWhere('alamat', 'like', '%'.$keyword.'%')
                        ->orWhere('nama_yayasan', 'like', '%'.$keyword.'%')
                        ->orWhere('nama_penanggungjawab', 'like', '%'.$keyword.'%')
                        ->orWhere('no_penanggungjawab', 'like', '%'.$keyword.'%');
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data SPPG berhasil diambil.',
            'data' => $sppg->items(),
            'meta' => [
                'current_page' => $sppg->currentPage(),
                'last_page' => $sppg->lastPage(),
                'per_page' => $sppg->perPage(),
                'total' => $sppg->total(),
                'from' => $sppg->firstItem(),
                'to' => $sppg->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatePayload($request);

        $sppg = Sppg::query()->create($payload);

        return response()->json([
            'message' => 'SPPG berhasil ditambahkan.',
            'data' => $sppg,
        ], 201);
    }

    public function show(Sppg $sppg): JsonResponse
    {
        return response()->json([
            'message' => 'Detail SPPG berhasil diambil.',
            'data' => $sppg,
        ]);
    }

    public function update(Request $request, Sppg $sppg): JsonResponse
    {
        $payload = $this->validatePayload($request, $sppg);

        $sppg->update($payload);

        return response()->json([
            'message' => 'SPPG berhasil diperbarui.',
            'data' => $sppg->fresh(),
        ]);
    }

    public function destroy(Sppg $sppg): JsonResponse
    {
        $sppg->delete();

        return response()->json([
            'message' => 'SPPG berhasil dihapus.',
        ]);
    }

    /**
     * @return array{
     *     nama_sppg: string,
     *     alamat: string,
     *     nama_yayasan: string,
     *     nama_penanggungjawab: string,
     *     no_penanggungjawab: string
     * }
     */
    private function validatePayload(Request $request, ?Sppg $ignoreSppg = null): array
    {
        $payload = $request->validate([
            'nama_sppg' => ['required', 'string', 'max:100'],
            'alamat' => ['required', 'string'],
            'nama_yayasan' => ['required', 'string', 'max:100'],
            'nama_penanggungjawab' => ['required', 'string', 'max:100'],
            'no_penanggungjawab' => ['required', 'string', 'min:10', 'max:20', 'regex:/^([0-9\\s\\-\\+\\(\\)]*)$/'],
        ], [
            'no_penanggungjawab.regex' => 'No HP hanya boleh berisi angka dan karakter khusus tertentu.',
            'no_penanggungjawab.min' => 'No HP minimal 10 karakter.',
            'no_penanggungjawab.max' => 'No HP maksimal 20 karakter.',
        ]);

        $normalizedNamaSppg = mb_strtolower(trim($payload['nama_sppg']));
        $normalizedAlamat = mb_strtolower(trim($payload['alamat']));
        $normalizedNamaYayasan = mb_strtolower(trim($payload['nama_yayasan']));
        $normalizedPenanggungJawab = mb_strtolower(trim($payload['nama_penanggungjawab']));
        $normalizedNoPenanggungJawab = $this->normalizePhone($payload['no_penanggungjawab']);

        $duplicateExists = Sppg::query()
            ->when($ignoreSppg !== null, fn ($query) => $query->whereKeyNot($ignoreSppg->id))
            ->whereRaw('LOWER(TRIM(nama_sppg)) = ?', [$normalizedNamaSppg])
            ->whereRaw('LOWER(TRIM(alamat)) = ?', [$normalizedAlamat])
            ->whereRaw('LOWER(TRIM(nama_yayasan)) = ?', [$normalizedNamaYayasan])
            ->whereRaw('LOWER(TRIM(nama_penanggungjawab)) = ?', [$normalizedPenanggungJawab])
            ->get()
            ->contains(function (Sppg $sppg) use ($normalizedNoPenanggungJawab): bool {
                return $this->normalizePhone((string) $sppg->no_penanggungjawab) === $normalizedNoPenanggungJawab;
            });

        if ($duplicateExists) {
            abort(response()->json([
                'message' => 'SPPG dengan nama, alamat, yayasan, penanggung jawab, dan no HP yang sama sudah ada.',
                'errors' => [
                    'nama_sppg' => ['SPPG dengan nama, alamat, yayasan, penanggung jawab, dan no HP yang sama sudah ada.'],
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
