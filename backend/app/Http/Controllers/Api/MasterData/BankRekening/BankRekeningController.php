<?php

namespace App\Http\Controllers\Api\MasterData\BankRekening;

use App\Http\Controllers\Controller;
use App\Models\MasterData\BankRekening;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BankRekeningController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'nama_bank', 'no_rek', 'atas_nama', 'cabang'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'nama_bank';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;

        $rekening = BankRekening::query()
            ->when($search, function ($query, string $keyword) {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('nama_bank', 'like', '%'.$keyword.'%')
                        ->orWhere('no_rek', 'like', '%'.$keyword.'%')
                        ->orWhere('atas_nama', 'like', '%'.$keyword.'%')
                        ->orWhere('cabang', 'like', '%'.$keyword.'%');
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data bank dan rekening berhasil diambil.',
            'data' => $rekening->items(),
            'meta' => [
                'current_page' => $rekening->currentPage(),
                'last_page' => $rekening->lastPage(),
                'per_page' => $rekening->perPage(),
                'total' => $rekening->total(),
                'from' => $rekening->firstItem(),
                'to' => $rekening->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatePayload($request);

        $rekening = BankRekening::query()->create($payload);

        return response()->json([
            'message' => 'Bank dan rekening berhasil ditambahkan.',
            'data' => $rekening,
        ], 201);
    }

    public function show(BankRekening $bankRekening): JsonResponse
    {
        return response()->json([
            'message' => 'Detail bank dan rekening berhasil diambil.',
            'data' => $bankRekening,
        ]);
    }

    public function update(Request $request, BankRekening $bankRekening): JsonResponse
    {
        $payload = $this->validatePayload($request, $bankRekening);

        $bankRekening->update($payload);

        return response()->json([
            'message' => 'Bank dan rekening berhasil diperbarui.',
            'data' => $bankRekening->fresh(),
        ]);
    }

    public function destroy(BankRekening $bankRekening): JsonResponse
    {
        $bankRekening->delete();

        return response()->json([
            'message' => 'Bank dan rekening berhasil dihapus.',
        ]);
    }

    /**
     * @return array{nama_bank: string, no_rek: string, atas_nama: string, cabang: string}
     */
    private function validatePayload(Request $request, ?BankRekening $ignoreBankRekening = null): array
    {
        $payload = $request->validate([
            'nama_bank' => ['required', 'string', 'max:100'],
            'no_rek' => ['required', 'string', 'min:5', 'max:50', 'regex:/^[0-9]+$/'],
            'atas_nama' => ['required', 'string', 'max:100'],
            'cabang' => ['required', 'string', 'max:100'],
        ], [
            'no_rek.regex' => 'No rekening hanya boleh berisi angka.',
            'no_rek.min' => 'No rekening minimal 5 digit.',
            'no_rek.max' => 'No rekening maksimal 50 digit.',
        ]);

        $normalizedNamaBank = mb_strtolower(trim($payload['nama_bank']));
        $normalizedNoRek = trim($payload['no_rek']);
        $normalizedAtasNama = mb_strtolower(trim($payload['atas_nama']));
        $normalizedCabang = mb_strtolower(trim($payload['cabang']));

        $query = BankRekening::query()
            ->when($ignoreBankRekening !== null, fn ($builder) => $builder->whereKeyNot($ignoreBankRekening->id));

        $duplicateNoRek = (clone $query)
            ->where('no_rek', $normalizedNoRek)
            ->exists();

        if ($duplicateNoRek) {
            abort(response()->json([
                'message' => 'Nomor rekening sudah digunakan.',
                'errors' => [
                    'no_rek' => ['Nomor rekening sudah digunakan.'],
                ],
            ], 422));
        }

        $duplicateExists = (clone $query)
            ->whereRaw('LOWER(TRIM(nama_bank)) = ?', [$normalizedNamaBank])
            ->where('no_rek', $normalizedNoRek)
            ->whereRaw('LOWER(TRIM(atas_nama)) = ?', [$normalizedAtasNama])
            ->whereRaw('LOWER(TRIM(cabang)) = ?', [$normalizedCabang])
            ->exists();

        if ($duplicateExists) {
            abort(response()->json([
                'message' => 'Bank dan rekening dengan data yang sama sudah ada.',
                'errors' => [
                    'nama_bank' => ['Bank dan rekening dengan data yang sama sudah ada.'],
                ],
            ], 422));
        }

        return $payload;
    }
}
