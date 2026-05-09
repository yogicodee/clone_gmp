<?php

namespace App\Http\Controllers\Api\TransaksiPenjualan\InvoicePenjualan;

use App\Http\Controllers\Controller;
use App\Models\MasterData\Sppg;
use App\Models\TransaksiPembelian\OrderPenawaranItem;
use App\Models\TransaksiPenjualan\InvoicePenjualan;
use App\Models\TransaksiPenjualan\Penjualan;
use App\Models\TransaksiPenjualan\TandaTerima;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvoicePenjualanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'nomor_invoice', 'tanggal_invoice', 'total_tagihan', 'status_pembayaran'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'tanggal_invoice';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 10;

        $records = InvoicePenjualan::query()
            ->with(['penjualan:id,kode_penjualan,tanggal', 'sppg:id,nama_sppg,alamat,no_penanggungjawab'])
            ->when($search, function ($query, string $keyword): void {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('nomor_invoice', 'like', '%'.$keyword.'%')
                        ->orWhere('status_pembayaran', 'like', '%'.$keyword.'%')
                        ->orWhereHas('sppg', function ($sppgQuery) use ($keyword): void {
                            $sppgQuery
                                ->where('nama_sppg', 'like', '%'.$keyword.'%')
                                ->orWhere('alamat', 'like', '%'.$keyword.'%')
                                ->orWhere('no_penanggungjawab', 'like', '%'.$keyword.'%');
                        });
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        $records->setCollection(
            $records->getCollection()->map(
                fn (InvoicePenjualan $invoicePenjualan): array => $this->serializeInvoice($invoicePenjualan)
            )
        );

        return response()->json([
            'message' => 'Data invoice penjualan berhasil diambil.',
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

    public function opsiSppgByTanggalKirim(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'tanggal_kirim' => ['required', 'date'],
        ]);

        $options = TandaTerima::query()
            ->with('sppg:id,nama_sppg,alamat,no_penanggungjawab')
            ->whereDate('tanggal', $filters['tanggal_kirim'])
            ->whereNotNull('sppg_id')
            ->orderBy('id')
            ->get()
            ->filter(fn (TandaTerima $tandaTerima): bool => $tandaTerima->sppg !== null)
            ->unique('sppg_id')
            ->map(function (TandaTerima $tandaTerima): array {
                $sppg = $tandaTerima->sppg;

                return [
                    'sppg_id' => $tandaTerima->sppg_id,
                    'nama_sppg' => $sppg?->nama_sppg,
                    'no_po' => $tandaTerima->no_po,
                    'alamat' => $sppg?->alamat,
                    'no_hp' => $sppg?->no_penanggungjawab,
                ];
            })
            ->values();

        return response()->json([
            'message' => 'Opsi SPPG invoice berhasil diambil.',
            'data' => $options,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatePayload($request);
        $record = InvoicePenjualan::query()->create($payload);

        return response()->json([
            'message' => 'Invoice penjualan berhasil ditambahkan.',
            'data' => $this->serializeInvoice(
                $record->fresh(['penjualan:id,kode_penjualan,tanggal', 'sppg:id,nama_sppg,alamat,no_penanggungjawab'])
            ),
        ], 201);
    }

    public function show(InvoicePenjualan $invoicePenjualan): JsonResponse
    {
        $invoicePenjualan->load([
            'penjualan:id,kode_penjualan,tanggal,total_harga,status',
            'sppg:id,nama_sppg,alamat,no_penanggungjawab',
        ]);

        return response()->json([
            'message' => 'Detail invoice penjualan berhasil diambil.',
            'data' => $this->serializeInvoice($invoicePenjualan, true),
        ]);
    }

    public function update(Request $request, InvoicePenjualan $invoicePenjualan): JsonResponse
    {
        $payload = $this->validatePayload($request, $invoicePenjualan);
        $invoicePenjualan->update($payload);

        return response()->json([
            'message' => 'Invoice penjualan berhasil diperbarui.',
            'data' => $this->serializeInvoice(
                $invoicePenjualan->fresh(['penjualan:id,kode_penjualan,tanggal', 'sppg:id,nama_sppg,alamat,no_penanggungjawab'])
            ),
        ]);
    }

    public function destroy(InvoicePenjualan $invoicePenjualan): JsonResponse
    {
        $invoicePenjualan->delete();

        return response()->json([
            'message' => 'Invoice penjualan berhasil dihapus.',
        ]);
    }

    private function validatePayload(Request $request, ?InvoicePenjualan $invoicePenjualan = null): array
    {
        $payload = $request->validate([
            'nomor_invoice' => [
                'required',
                'string',
                'max:50',
                Rule::unique('invoice_penjualan', 'nomor_invoice')->ignore($invoicePenjualan?->id),
            ],
            'tanggal_kirim' => ['required', 'date'],
            'sppg_id' => ['required', 'integer', 'exists:sppg,id'],
            'tanggal_invoice' => ['required', 'date'],
            'status_pembayaran' => ['required', Rule::in(['lunas', 'belum lunas'])],
        ]);

        $sppg = Sppg::query()->findOrFail($payload['sppg_id']);

        $tandaTerima = TandaTerima::query()
            ->whereDate('tanggal', $payload['tanggal_kirim'])
            ->where('sppg_id', $payload['sppg_id'])
            ->first();

        if ($tandaTerima === null) {
            throw ValidationException::withMessages([
                'sppg_id' => 'SPPG tersebut tidak memiliki tanda terima pada tanggal kirim yang dipilih.',
            ]);
        }

        $penjualanRecords = $this->findMatchingPenjualanRecords($payload['tanggal_kirim'], $sppg);

        if ($penjualanRecords->isEmpty()) {
            throw ValidationException::withMessages([
                'tanggal_kirim' => 'Belum ada detail penjualan untuk SPPG tersebut pada tanggal kirim yang dipilih.',
            ]);
        }

        $invalidStatus = $penjualanRecords->first(fn (Penjualan $penjualan): bool => $penjualan->status !== 'selesai');
        if ($invalidStatus !== null) {
            throw ValidationException::withMessages([
                'tanggal_kirim' => 'Invoice hanya boleh dibuat dari penjualan yang sudah selesai.',
            ]);
        }

        $representativePenjualan = $penjualanRecords->first();

        $duplicateInvoice = InvoicePenjualan::query()
            ->where('sppg_id', $payload['sppg_id'])
            ->whereHas('penjualan', function ($query) use ($payload): void {
                $query->whereDate('tanggal', $payload['tanggal_kirim']);
            })
            ->when($invoicePenjualan !== null, fn ($query) => $query->where('id', '!=', $invoicePenjualan->id))
            ->exists();

        if ($duplicateInvoice) {
            throw ValidationException::withMessages([
                'sppg_id' => 'Invoice untuk SPPG dan tanggal kirim tersebut sudah ada.',
            ]);
        }

        return [
            'nomor_invoice' => $payload['nomor_invoice'],
            'penjualan_id' => $representativePenjualan->id,
            'sppg_id' => $payload['sppg_id'],
            'tanggal_invoice' => $payload['tanggal_invoice'],
            'total_tagihan' => $penjualanRecords->sum(
                fn (Penjualan $penjualan): float => (float) $penjualan->total_harga
            ),
            'status_pembayaran' => $payload['status_pembayaran'],
        ];
    }

    private function serializeInvoice(InvoicePenjualan $invoicePenjualan, bool $includeDetailItems = false): array
    {
        $sppg = $invoicePenjualan->sppg;
        $tanggalKirim = $invoicePenjualan->penjualan?->tanggal?->format('Y-m-d');
        $detailItems = collect();
        $totalTagihan = (float) $invoicePenjualan->total_tagihan;
        $noPo = null;

        if ($sppg !== null && $tanggalKirim !== null) {
            $noPo = TandaTerima::query()
                ->whereDate('tanggal', $tanggalKirim)
                ->where('sppg_id', $sppg->id)
                ->value('no_po');

            $detailItems = $this->buildDetailItems($tanggalKirim, $sppg);
            $computedTotal = $detailItems->sum(fn (array $item): float => (float) $item['harga_total']);
            if ($computedTotal > 0) {
                $totalTagihan = $computedTotal;
            }
        }

        $data = [
            'id' => $invoicePenjualan->id,
            'nomor_invoice' => $invoicePenjualan->nomor_invoice,
            'penjualan_id' => $invoicePenjualan->penjualan_id,
            'no_po' => $noPo,
            'sppg_id' => $invoicePenjualan->sppg_id,
            'sppg' => $sppg?->nama_sppg,
            'alamat' => $sppg?->alamat,
            'no_hp' => $sppg?->no_penanggungjawab,
            'tanggal_kirim' => $tanggalKirim,
            'tanggal_invoice' => $invoicePenjualan->tanggal_invoice?->format('Y-m-d'),
            'total_tagihan' => number_format($totalTagihan, 2, '.', ''),
            'status_pembayaran' => $invoicePenjualan->status_pembayaran,
        ];

        if ($includeDetailItems) {
            $data['detail_items'] = $detailItems->values()->all();
        }

        return $data;
    }

    private function buildDetailItems(string $tanggalKirim, Sppg $sppg): Collection
    {
        return $this->findMatchingPenjualanRecords($tanggalKirim, $sppg)
            ->flatMap(function (Penjualan $penjualan): Collection {
                $items = $this->resolvePenjualanItems($penjualan);

                return $items->map(fn ($item): array => [
                    'id' => $item->id,
                    'nama_barang' => $item->nama_barang,
                    'qty' => (float) $item->qty,
                    'satuan' => $item->satuan,
                    'harga_satuan' => (float) $item->harga_satuan,
                    'harga_total' => (float) ($item->total_harga ?? $item->harga_total ?? 0),
                ]);
            })
            ->values();
    }

    private function resolvePenjualanItems(Penjualan $penjualan): Collection
    {
        $items = collect(
            $penjualan->relationLoaded('items')
                ? $penjualan->items
                : $penjualan->items()->orderBy('id')->get()
        );

        if ($items->isNotEmpty() || $penjualan->order_penawaran_id === null) {
            return $items;
        }

        return OrderPenawaranItem::query()
            ->where('order_penawaran_id', $penjualan->order_penawaran_id)
            ->orderBy('id')
            ->get()
            ->map(function (OrderPenawaranItem $item): object {
                return (object) [
                    'id' => $item->id,
                    'nama_barang' => $item->nama_barang,
                    'qty' => (float) $item->qty,
                    'satuan' => $item->satuan,
                    'harga_satuan' => (float) $item->harga_satuan,
                    'harga_total' => (float) $item->qty * (float) $item->harga_satuan,
                ];
            })
            ->values();
    }

    private function findMatchingPenjualanRecords(string $tanggalKirim, Sppg $sppg): EloquentCollection
    {
        return Penjualan::query()
            ->with(['items' => fn ($query) => $query->orderBy('id')])
            ->whereDate('tanggal', $tanggalKirim)
            ->whereHas('orderPenawaran', function ($query) use ($sppg): void {
                $query->where('nama_pembeli', $sppg->nama_sppg);
            })
            ->orderBy('id')
            ->get();
    }
}
