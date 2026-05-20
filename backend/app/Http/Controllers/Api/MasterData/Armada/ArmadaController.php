<?php

namespace App\Http\Controllers\Api\MasterData\Armada;

use App\Http\Controllers\Controller;
use App\Models\MasterData\Armada;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArmadaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'nama_unit', 'no_pol', 'jenis_kendaraan'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'nama_unit';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;

        $armada = Armada::query()
            ->when($search, function ($query, string $keyword) {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('nama_unit', 'like', '%'.$keyword.'%')
                        ->orWhere('no_pol', 'like', '%'.$keyword.'%')
                        ->orWhere('jenis_kendaraan', 'like', '%'.$keyword.'%');
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data armada berhasil diambil.',
            'data' => $armada->items(),
            'meta' => [
                'current_page' => $armada->currentPage(),
                'last_page' => $armada->lastPage(),
                'per_page' => $armada->perPage(),
                'total' => $armada->total(),
                'from' => $armada->firstItem(),
                'to' => $armada->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatePayload($request);

        $armada = Armada::query()->create($payload);

        return response()->json([
            'message' => 'Armada berhasil ditambahkan.',
            'data' => $armada,
        ], 201);
    }

    public function show(Armada $armada): JsonResponse
    {
        return response()->json([
            'message' => 'Detail armada berhasil diambil.',
            'data' => $armada,
        ]);
    }

    public function update(Request $request, Armada $armada): JsonResponse
    {
        $payload = $this->validatePayload($request, $armada);

        $armada->update($payload);

        return response()->json([
            'message' => 'Armada berhasil diperbarui.',
            'data' => $armada->fresh(),
        ]);
    }

    public function destroy(Armada $armada): JsonResponse
    {
        $armada->delete();

        return response()->json([
            'message' => 'Armada berhasil dihapus.',
        ]);
    }

    /**
     * @return array{nama_unit: string, no_pol: string, jenis_kendaraan: string}
     */
    private function validatePayload(Request $request, ?Armada $ignoreArmada = null): array
    {
        $payload = $request->validate([
            'nama_unit' => ['required', 'string', 'max:100'],
            'no_pol' => ['required', 'string', 'max:20'],
            'jenis_kendaraan' => ['required', Rule::in(['Roda 2', 'Roda 4'])],
        ], [
            'jenis_kendaraan.in' => 'Jenis kendaraan hanya boleh Roda 2 atau Roda 4.',
        ]);

        $normalizedNamaUnit = mb_strtolower(trim($payload['nama_unit']));
        $normalizedNoPol = $this->normalizePlate($payload['no_pol']);
        $normalizedJenisKendaraan = mb_strtolower(trim($payload['jenis_kendaraan']));

        $query = Armada::query()
            ->when($ignoreArmada !== null, fn ($builder) => $builder->whereKeyNot($ignoreArmada->id));

        $duplicateNoPol = (clone $query)
            ->get()
            ->contains(function (Armada $armada) use ($normalizedNoPol): bool {
                return $this->normalizePlate((string) $armada->no_pol) === $normalizedNoPol;
            });

        if ($duplicateNoPol) {
            abort(response()->json([
                'message' => 'Nomor polisi armada sudah digunakan.',
                'errors' => [
                    'no_pol' => ['Nomor polisi armada sudah digunakan.'],
                ],
            ], 422));
        }

        $duplicateExists = (clone $query)
            ->whereRaw('LOWER(TRIM(nama_unit)) = ?', [$normalizedNamaUnit])
            ->whereRaw('LOWER(TRIM(jenis_kendaraan)) = ?', [$normalizedJenisKendaraan])
            ->get()
            ->contains(function (Armada $armada) use ($normalizedNoPol): bool {
                return $this->normalizePlate((string) $armada->no_pol) === $normalizedNoPol;
            });

        if ($duplicateExists) {
            abort(response()->json([
                'message' => 'Armada dengan nama unit, nomor polisi, dan jenis kendaraan yang sama sudah ada.',
                'errors' => [
                    'nama_unit' => ['Armada dengan nama unit, nomor polisi, dan jenis kendaraan yang sama sudah ada.'],
                ],
            ], 422));
        }

        return $payload;
    }

    private function normalizePlate(string $value): string
    {
        return mb_strtolower(preg_replace('/\s+/', '', trim($value)) ?? '');
    }
}
