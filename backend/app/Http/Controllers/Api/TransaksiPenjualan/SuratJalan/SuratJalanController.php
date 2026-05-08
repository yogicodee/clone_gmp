<?php

namespace App\Http\Controllers\Api\TransaksiPenjualan\SuratJalan;

use App\Http\Controllers\Controller;
use App\Models\TransaksiPenjualan\Penjualan;
use App\Models\TransaksiPenjualan\SuratJalan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SuratJalanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'nomor_surat_jalan', 'no_po', 'tanggal', 'status'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'tanggal';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 10;

        $records = SuratJalan::query()
            ->with(['sppg:id,nama_sppg', 'armadaRef:id,nama_unit,no_pol', 'driver:id,nama'])
            ->when($search, function ($query, string $keyword): void {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('nomor_surat_jalan', 'like', '%'.$keyword.'%')
                        ->orWhere('no_po', 'like', '%'.$keyword.'%')
                        ->orWhereHas('sppg', fn ($sppgQuery) => $sppgQuery->where('nama_sppg', 'like', '%'.$keyword.'%'))
                        ->orWhereHas('driver', fn ($driverQuery) => $driverQuery->where('nama', 'like', '%'.$keyword.'%'));
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data surat jalan berhasil diambil.',
            'data' => $records->items(),
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
                'from' => $records->firstItem(),
                'to' => $records->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $record = DB::transaction(function () use ($request): SuratJalan {
            $record = SuratJalan::query()->create($this->validatePayload($request));
            $this->syncItemsFromPenjualan($record);

            return $record;
        });

        return response()->json([
            'message' => 'Data surat jalan berhasil ditambahkan.',
            'data' => $record->fresh(['sppg:id,nama_sppg', 'armadaRef:id,nama_unit,no_pol', 'driver:id,nama', 'items.penjualanItem']),
        ], 201);
    }

    public function show(SuratJalan $suratJalan): JsonResponse
    {
        $this->syncItemsFromPenjualan($suratJalan);

        $suratJalan->load([
            'sppg:id,nama_sppg',
            'armadaRef:id,nama_unit,no_pol',
            'driver:id,nama',
            'items.penjualanItem',
        ]);

        return response()->json([
            'message' => 'Detail surat jalan berhasil diambil.',
            'data' => $suratJalan,
        ]);
    }

    public function update(Request $request, SuratJalan $suratJalan): JsonResponse
    {
        DB::transaction(function () use ($request, $suratJalan): void {
            $suratJalan->update($this->validatePayload($request, $suratJalan));
            $this->syncItemsFromPenjualan($suratJalan);
        });

        return response()->json([
            'message' => 'Data surat jalan berhasil diperbarui.',
            'data' => $suratJalan->fresh(['sppg:id,nama_sppg', 'armadaRef:id,nama_unit,no_pol', 'driver:id,nama', 'items.penjualanItem']),
        ]);
    }

    public function destroy(SuratJalan $suratJalan): JsonResponse
    {
        $suratJalan->delete();

        return response()->json([
            'message' => 'Data surat jalan berhasil dihapus.',
        ]);
    }

    private function validatePayload(Request $request, ?SuratJalan $suratJalan = null): array
    {
        return $request->validate([
            'nomor_surat_jalan' => [
                'required',
                'string',
                'max:50',
                Rule::unique('surat_jalan', 'nomor_surat_jalan')->ignore($suratJalan?->id),
            ],
            'no_po' => ['nullable', 'string', 'max:50'],
            'tanggal' => ['required', 'date'],
            'sppg_id' => ['nullable', 'integer', 'exists:sppg,id'],
            'armada_id' => ['nullable', 'integer', 'exists:armada,id'],
            'driver_id' => ['nullable', 'integer', 'exists:karyawan,id'],
            'status' => ['required', Rule::in(['draft', 'selesai', 'batal'])],
        ]);
    }

    private function syncItemsFromPenjualan(SuratJalan $suratJalan): void
    {
        if ($suratJalan->tanggal === null) {
            $suratJalan->items()->delete();

            return;
        }

        $sourceItems = Penjualan::query()
            ->with('items')
            ->whereDate('tanggal', $suratJalan->tanggal)
            ->orderBy('id')
            ->get()
            ->flatMap(fn (Penjualan $penjualan) => $penjualan->items)
            ->values();

        $existingItems = $suratJalan->items()
            ->whereNotNull('penjualan_item_id')
            ->get()
            ->keyBy('penjualan_item_id');

        $sourceIds = $sourceItems
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($sourceItems as $sourceItem) {
            $currentItem = $existingItems->get($sourceItem->id);

            $suratJalan->items()->updateOrCreate(
                [
                    'surat_jalan_id' => $suratJalan->id,
                    'penjualan_item_id' => $sourceItem->id,
                ],
                [
                    'nama_barang' => $sourceItem->nama_barang,
                    'qty' => $sourceItem->qty,
                    'satuan' => $sourceItem->satuan,
                    'keterangan' => $currentItem?->keterangan,
                ]
            );
        }

        $suratJalan->items()
            ->where(function ($query) use ($sourceIds): void {
                $query->whereNull('penjualan_item_id');

                if (! empty($sourceIds)) {
                    $query->orWhereNotIn('penjualan_item_id', $sourceIds);
                }
            })
            ->delete();
    }
}
