<?php

namespace App\Http\Controllers\Api\TransaksiPenjualan\TandaTerima;

use App\Http\Controllers\Controller;
use App\Models\TransaksiPenjualan\SuratJalan;
use App\Models\TransaksiPenjualan\SuratJalanItem;
use App\Models\TransaksiPenjualan\TandaTerima;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TandaTerimaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'nomor_tanda_terima', 'nomor_surat_jalan', 'no_po', 'tanggal', 'status'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'tanggal';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 10;

        $records = TandaTerima::query()
            ->with(['sppg:id,nama_sppg', 'armadaRef:id,nama_unit,no_pol', 'akuntan:id,nama', 'driver:id,nama'])
            ->when($search, function ($query, string $keyword): void {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('nomor_tanda_terima', 'like', '%'.$keyword.'%')
                        ->orWhere('nomor_surat_jalan', 'like', '%'.$keyword.'%')
                        ->orWhere('no_po', 'like', '%'.$keyword.'%')
                        ->orWhereHas('sppg', fn ($sppgQuery) => $sppgQuery->where('nama_sppg', 'like', '%'.$keyword.'%'))
                        ->orWhereHas('akuntan', fn ($karyawanQuery) => $karyawanQuery->where('nama', 'like', '%'.$keyword.'%'))
                        ->orWhereHas('driver', fn ($karyawanQuery) => $karyawanQuery->where('nama', 'like', '%'.$keyword.'%'));
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data tanda terima berhasil diambil.',
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
        $payload = $request->validate([
            'tanggal' => ['required', 'date'],
        ]);

        $suratJalanRecords = SuratJalan::query()
            ->with('items')
            ->whereDate('tanggal', $payload['tanggal'])
            ->orderBy('id')
            ->get();

        if ($suratJalanRecords->isEmpty()) {
            throw ValidationException::withMessages([
                'tanggal' => 'Belum ada surat jalan pada tanggal tersebut.',
            ]);
        }

        $records = $suratJalanRecords
            ->map(fn (SuratJalan $suratJalan) => $this->syncFromSuratJalan($suratJalan))
            ->values();

        return response()->json([
            'message' => 'Data tanda terima berhasil disinkronkan dari surat jalan.',
            'data' => $records,
        ], 201);
    }

    public function show(TandaTerima $tandaTerima): JsonResponse
    {
        $tandaTerima->load([
            'sppg:id,nama_sppg',
            'armadaRef:id,nama_unit,no_pol',
            'akuntan:id,nama',
            'driver:id,nama',
            'items.penjualanItem',
        ]);

        return response()->json([
            'message' => 'Detail tanda terima berhasil diambil.',
            'data' => $tandaTerima,
        ]);
    }

    public function update(Request $request, TandaTerima $tandaTerima): JsonResponse
    {
        $tandaTerima->update($this->validatePayload($request, $tandaTerima));

        return response()->json([
            'message' => 'Data tanda terima berhasil diperbarui.',
            'data' => $tandaTerima->fresh(['sppg:id,nama_sppg', 'armadaRef:id,nama_unit,no_pol', 'akuntan:id,nama', 'driver:id,nama']),
        ]);
    }

    public function destroy(TandaTerima $tandaTerima): JsonResponse
    {
        $tandaTerima->delete();

        return response()->json([
            'message' => 'Data tanda terima berhasil dihapus.',
        ]);
    }

    private function validatePayload(Request $request, ?TandaTerima $tandaTerima = null): array
    {
        $payload = $request->validate([
            'nomor_tanda_terima' => [
                'required',
                'string',
                'max:50',
                Rule::unique('tanda_terima', 'nomor_tanda_terima')->ignore($tandaTerima?->id),
            ],
            'nomor_surat_jalan' => ['required', 'string', 'max:50'],
            'no_po' => ['nullable', 'string', 'max:50'],
            'tanggal' => ['required', 'date'],
            'sppg_id' => ['nullable', 'integer', 'exists:sppg,id'],
            'armada_id' => ['nullable', 'integer', 'exists:armada,id'],
            'akuntan_id' => ['nullable', 'integer', 'exists:karyawan,id'],
            'driver_id' => ['nullable', 'integer', 'exists:karyawan,id'],
            'status' => ['required', Rule::in(['draft', 'selesai', 'batal'])],
        ]);

        $suratJalan = SuratJalan::query()
            ->where('nomor_surat_jalan', $payload['nomor_surat_jalan'])
            ->first();

        if ($suratJalan === null) {
            throw ValidationException::withMessages([
                'nomor_surat_jalan' => 'Nomor surat jalan belum terdaftar.',
            ]);
        }

        if ($suratJalan->tanggal?->format('Y-m-d') !== $payload['tanggal']) {
            throw ValidationException::withMessages([
                'tanggal' => 'Tanggal tanda terima harus sama dengan tanggal surat jalan.',
            ]);
        }

        foreach ([
            'no_po' => 'no_po',
            'sppg_id' => 'sppg_id',
            'armada_id' => 'armada_id',
            'driver_id' => 'driver_id',
        ] as $payloadKey => $suratJalanKey) {
            if (
                array_key_exists($payloadKey, $payload)
                && $payload[$payloadKey] !== null
                && (string) $payload[$payloadKey] !== (string) $suratJalan->{$suratJalanKey}
            ) {
                throw ValidationException::withMessages([
                    $payloadKey => 'Data tanda terima harus konsisten dengan surat jalan terkait.',
                ]);
            }
        }

        $payload['no_po'] = $payload['no_po'] ?? $suratJalan->no_po;
        $payload['sppg_id'] = $payload['sppg_id'] ?? $suratJalan->sppg_id;
        $payload['armada_id'] = $payload['armada_id'] ?? $suratJalan->armada_id;
        $payload['driver_id'] = $payload['driver_id'] ?? $suratJalan->driver_id;

        return $payload;
    }

    private function syncFromSuratJalan(SuratJalan $suratJalan): TandaTerima
    {
        $record = TandaTerima::query()
            ->where('nomor_surat_jalan', $suratJalan->nomor_surat_jalan)
            ->first();

        if ($record === null) {
            $record = new TandaTerima();
            $record->nomor_tanda_terima = $this->generateNomorTandaTerima($suratJalan);
            $record->status = 'draft';
        }

        $record->nomor_surat_jalan = $suratJalan->nomor_surat_jalan;
        $record->no_po = $suratJalan->no_po;
        $record->tanggal = $suratJalan->tanggal;
        $record->sppg_id = $suratJalan->sppg_id;
        $record->armada_id = $suratJalan->armada_id;
        $record->driver_id = $suratJalan->driver_id;
        $record->save();

        $this->syncItemsFromSuratJalan($record, $suratJalan);

        return $record->fresh([
            'sppg:id,nama_sppg',
            'armadaRef:id,nama_unit,no_pol',
            'akuntan:id,nama',
            'driver:id,nama',
            'items',
        ]);
    }

    private function syncItemsFromSuratJalan(TandaTerima $tandaTerima, SuratJalan $suratJalan): void
    {
        $tandaTerima->items()->delete();

        $suratJalanItems = $suratJalan->relationLoaded('items')
            ? $suratJalan->items
            : $suratJalan->items()->orderBy('id')->get();

        $suratJalanItems->each(function (SuratJalanItem $item) use ($tandaTerima): void {
            $tandaTerima->items()->create([
                'penjualan_item_id' => $item->penjualan_item_id,
                'nama_barang' => $item->nama_barang,
                'qty' => $item->qty,
                'satuan' => $item->satuan,
                'keterangan' => $item->keterangan,
            ]);
        });
    }

    private function generateNomorTandaTerima(SuratJalan $suratJalan): string
    {
        $base = 'TT-' . trim((string) $suratJalan->nomor_surat_jalan);
        $nomor = $base;
        $counter = 2;

        while (
            TandaTerima::query()
                ->where('nomor_tanda_terima', $nomor)
                ->exists()
        ) {
            $nomor = $base . '-' . $counter;
            $counter++;
        }

        return $nomor;
    }
}
