<?php

namespace App\Http\Controllers\Api\MasterData\Mitra;

use App\Http\Controllers\Controller;
use App\Models\MasterData\Mitra;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MitraController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'nama_yayasan', 'alamat', 'nama_pic', 'no_pic'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'nama_yayasan';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;

        $mitra = Mitra::query()
            ->when($search, function ($query, string $keyword) {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('nama_yayasan', 'like', '%'.$keyword.'%')
                        ->orWhere('alamat', 'like', '%'.$keyword.'%')
                        ->orWhere('nama_pic', 'like', '%'.$keyword.'%')
                        ->orWhere('no_pic', 'like', '%'.$keyword.'%');
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data mitra berhasil diambil.',
            'data' => $mitra->items(),
            'meta' => [
                'current_page' => $mitra->currentPage(),
                'last_page' => $mitra->lastPage(),
                'per_page' => $mitra->perPage(),
                'total' => $mitra->total(),
                'from' => $mitra->firstItem(),
                'to' => $mitra->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatePayload($request);

        $mitra = Mitra::query()->create($payload);

        return response()->json([
            'message' => 'Mitra berhasil ditambahkan.',
            'data' => $mitra,
        ], 201);
    }

    public function show(Mitra $mitra): JsonResponse
    {
        return response()->json([
            'message' => 'Detail mitra berhasil diambil.',
            'data' => $mitra,
        ]);
    }

    public function update(Request $request, Mitra $mitra): JsonResponse
    {
        $payload = $this->validatePayload($request, $mitra);

        $mitra->update($payload);

        return response()->json([
            'message' => 'Mitra berhasil diperbarui.',
            'data' => $mitra->fresh(),
        ]);
    }

    public function destroy(Mitra $mitra): JsonResponse
    {
        $mitra->delete();

        return response()->json([
            'message' => 'Mitra berhasil dihapus.',
        ]);
    }

    /**
     * @return array{nama_yayasan: string, alamat: string, nama_pic: string, no_pic: string}
     */
    private function validatePayload(Request $request, ?Mitra $ignoreMitra = null): array
    {
        $payload = $request->validate([
            'nama_yayasan' => ['required', 'string', 'max:100'],
            'alamat' => ['required', 'string'],
            'nama_pic' => ['required', 'string', 'max:100'],
            'no_pic' => ['required', 'string', 'min:10', 'max:20', 'regex:/^([0-9\\s\\-\\+\\(\\)]*)$/'],
        ], [
            'no_pic.regex' => 'No PIC hanya boleh berisi angka dan karakter khusus tertentu.',
            'no_pic.min' => 'No PIC minimal 10 karakter.',
            'no_pic.max' => 'No PIC maksimal 20 karakter.',
        ]);

        $normalizedNamaYayasan = mb_strtolower(trim($payload['nama_yayasan']));
        $normalizedAlamat = mb_strtolower(trim($payload['alamat']));
        $normalizedNamaPic = mb_strtolower(trim($payload['nama_pic']));
        $normalizedNoPic = $this->normalizePhone($payload['no_pic']);

        $duplicateExists = Mitra::query()
            ->when($ignoreMitra !== null, fn ($query) => $query->whereKeyNot($ignoreMitra->id))
            ->whereRaw('LOWER(TRIM(nama_yayasan)) = ?', [$normalizedNamaYayasan])
            ->whereRaw('LOWER(TRIM(alamat)) = ?', [$normalizedAlamat])
            ->whereRaw('LOWER(TRIM(nama_pic)) = ?', [$normalizedNamaPic])
            ->get()
            ->contains(function (Mitra $mitra) use ($normalizedNoPic): bool {
                return $this->normalizePhone((string) $mitra->no_pic) === $normalizedNoPic;
            });

        if ($duplicateExists) {
            abort(response()->json([
                'message' => 'Mitra dengan nama yayasan, alamat, PIC, dan no PIC yang sama sudah ada.',
                'errors' => [
                    'nama_yayasan' => ['Mitra dengan nama yayasan, alamat, PIC, dan no PIC yang sama sudah ada.'],
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
