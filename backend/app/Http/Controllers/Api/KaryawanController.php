<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KaryawanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in([
                'id',
                'nama',
                'alamat',
                'no_hp',
                'jabatan',
                'tanggal_masuk',
                'status',
            ])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'nama';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;

        $karyawan = Karyawan::query()
            ->when($search, function ($query, string $keyword) {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('nama', 'like', '%'.$keyword.'%')
                        ->orWhere('jabatan', 'like', '%'.$keyword.'%')
                        ->orWhere('alamat', 'like', '%'.$keyword.'%')
                        ->orWhere('no_hp', 'like', '%'.$keyword.'%');
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data karyawan berhasil diambil.',
            'data' => array_map(
                fn (Karyawan $item): array => $this->transformKaryawan($item),
                $karyawan->items()
            ),
            'meta' => [
                'current_page' => $karyawan->currentPage(),
                'last_page' => $karyawan->lastPage(),
                'per_page' => $karyawan->perPage(),
                'total' => $karyawan->total(),
                'from' => $karyawan->firstItem(),
                'to' => $karyawan->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatePayload($request);

        $karyawan = Karyawan::query()->create($payload);

        return response()->json([
            'message' => 'Karyawan berhasil ditambahkan.',
            'data' => $this->transformKaryawan($karyawan),
        ], 201);
    }

    public function show(Karyawan $karyawan): JsonResponse
    {
        return response()->json([
            'message' => 'Detail karyawan berhasil diambil.',
            'data' => $this->transformKaryawan($karyawan),
        ]);
    }

    public function update(Request $request, Karyawan $karyawan): JsonResponse
    {
        $payload = $this->validatePayload($request);

        $karyawan->update($payload);

        return response()->json([
            'message' => 'Karyawan berhasil diperbarui.',
            'data' => $this->transformKaryawan($karyawan->fresh()),
        ]);
    }

    public function destroy(Karyawan $karyawan): JsonResponse
    {
        $karyawan->delete();

        return response()->json([
            'message' => 'Karyawan berhasil dihapus.',
        ]);
    }

    /**
     * @return array{
     *     nama: string,
     *     alamat: string,
     *     no_hp: string,
     *     jabatan: string,
     *     tanggal_masuk: string,
     *     status: string
     * }
     */
    private function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'alamat' => ['required', 'string'],
            'no_hp' => ['required', 'string', 'min:10', 'max:20', 'regex:/^([0-9\\s\\-\\+\\(\\)]*)$/'],
            'jabatan' => ['required', 'string', 'max:50'],
            'tanggal_masuk' => ['required', 'date'],
            'status' => ['required', Rule::in(['aktif', 'non aktif', 'nonaktif'])],
        ], [
            'no_hp.regex' => 'No HP hanya boleh berisi angka dan karakter khusus tertentu.',
            'no_hp.min' => 'No HP minimal 10 karakter.',
            'no_hp.max' => 'No HP maksimal 20 karakter.',
            'status.in' => 'Status hanya boleh aktif atau non aktif.',
        ]);

        return [
            'nama' => $validated['nama'],
            'alamat' => $validated['alamat'],
            'no_hp' => $validated['no_hp'],
            'jabatan' => $validated['jabatan'],
            'tanggal_masuk' => $validated['tanggal_masuk'],
            'status' => $validated['status'] === 'non aktif' ? 'nonaktif' : $validated['status'],
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     nama: string|null,
     *     alamat: string|null,
     *     no_hp: string|null,
     *     jabatan: string|null,
     *     tanggal_masuk: string|null,
     *     status: string|null
     * }
     */
    private function transformKaryawan(Karyawan $karyawan): array
    {
        return [
            'id' => $karyawan->id,
            'nama' => $karyawan->nama,
            'alamat' => $karyawan->alamat,
            'no_hp' => $karyawan->no_hp,
            'jabatan' => $karyawan->jabatan,
            'tanggal_masuk' => $karyawan->tanggal_masuk,
            'status' => $karyawan->status === 'nonaktif' ? 'non aktif' : $karyawan->status,
        ];
    }
}
