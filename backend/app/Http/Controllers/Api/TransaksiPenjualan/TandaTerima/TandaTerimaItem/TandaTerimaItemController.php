<?php

namespace App\Http\Controllers\Api\TransaksiPenjualan\TandaTerima\TandaTerimaItem;

use App\Http\Controllers\Controller;
use App\Models\TransaksiPembelian\OrderPenawaranItem;
use App\Models\TransaksiPenjualan\Penjualan;
use App\Models\TransaksiPenjualan\PenjualanItem;
use App\Models\TransaksiPenjualan\SuratJalan;
use App\Models\TransaksiPenjualan\TandaTerima;
use App\Models\TransaksiPenjualan\TandaTerimaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TandaTerimaItemController extends Controller
{
    public function index(Request $request, TandaTerima $tandaTerima): JsonResponse
    {
        $this->syncLinkedSuratJalanItems($tandaTerima);

        $filters = $request->validate([
            'search' => ['nullable', 'string'],
        ]);

        $search = $filters['search'] ?? null;

        $items = $tandaTerima->items()
            ->when($search, function ($query, string $keyword): void {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('nama_barang', 'like', '%'.$keyword.'%')
                        ->orWhere('keterangan', 'like', '%'.$keyword.'%');
                });
            })
            ->orderBy('id')
            ->get();

        return response()->json([
            'message' => 'Data item tanda terima berhasil diambil.',
            'data' => $items,
        ]);
    }

    public function store(Request $request, TandaTerima $tandaTerima): JsonResponse
    {
        $payload = $this->validatePayload($request);
        $item = $this->persistItem(new TandaTerimaItem(), $tandaTerima, $payload);

        return response()->json([
            'message' => 'Item tanda terima berhasil ditambahkan.',
            'data' => $item,
        ], 201);
    }

    public function show(TandaTerima $tandaTerima, TandaTerimaItem $item): JsonResponse
    {
        $this->syncLinkedSuratJalanItems($tandaTerima);
        $this->ensureItemBelongsToTandaTerima($tandaTerima, $item);

        return response()->json([
            'message' => 'Detail item tanda terima berhasil diambil.',
            'data' => $item,
        ]);
    }

    public function update(Request $request, TandaTerima $tandaTerima, TandaTerimaItem $item): JsonResponse
    {
        $this->ensureItemBelongsToTandaTerima($tandaTerima, $item);
        $payload = $this->validatePayload($request);
        $item = $this->persistItem($item, $tandaTerima, $payload);

        return response()->json([
            'message' => 'Item tanda terima berhasil diperbarui.',
            'data' => $item,
        ]);
    }

    public function destroy(TandaTerima $tandaTerima, TandaTerimaItem $item): JsonResponse
    {
        $this->ensureItemBelongsToTandaTerima($tandaTerima, $item);
        $item->delete();

        return response()->json([
            'message' => 'Item tanda terima berhasil dihapus.',
        ]);
    }

    public function opsiBarang(TandaTerima $tandaTerima): JsonResponse
    {
        $this->syncLinkedSuratJalanItems($tandaTerima);

        $suratJalan = SuratJalan::query()
            ->where('nomor_surat_jalan', $tandaTerima->nomor_surat_jalan)
            ->first();

        $options = $suratJalan === null
            ? collect()
            : $suratJalan->items()
                ->whereNotNull('penjualan_item_id')
                ->orderBy('nama_barang')
                ->get([
                    'penjualan_item_id as id',
                    'nama_barang',
                    'qty',
                    'satuan',
                ]);

        return response()->json([
            'message' => 'Opsi barang tanda terima berhasil diambil.',
            'data' => $options->values(),
        ]);
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'penjualan_item_id' => ['nullable', 'integer', 'exists:penjualan_items,id'],
            'nama_barang' => ['required_without:penjualan_item_id', 'string', 'max:100'],
            'qty' => ['required_without:penjualan_item_id', 'numeric', 'gt:0'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'keterangan' => ['nullable', 'string'],
        ]);
    }

    private function persistItem(TandaTerimaItem $item, TandaTerima $tandaTerima, array $payload): TandaTerimaItem
    {
        $sourceItem = isset($payload['penjualan_item_id'])
            ? PenjualanItem::query()->findOrFail($payload['penjualan_item_id'])
            : null;

        if ($sourceItem !== null) {
            $this->ensureSourceItemExistsInLinkedSuratJalan($tandaTerima, $sourceItem);
        }

        $item->fill([
            'tanda_terima_id' => $tandaTerima->id,
            'penjualan_item_id' => $sourceItem?->id,
            'nama_barang' => $sourceItem?->nama_barang ?? $payload['nama_barang'],
            'qty' => $sourceItem?->qty ?? $payload['qty'],
            'satuan' => $sourceItem?->satuan ?? ($payload['satuan'] ?? null),
            'keterangan' => $payload['keterangan'] ?? null,
        ]);

        $item->save();

        return $item->fresh();
    }

    private function ensureItemBelongsToTandaTerima(TandaTerima $tandaTerima, TandaTerimaItem $item): void
    {
        abort_if($item->tanda_terima_id !== $tandaTerima->id, 404);
    }

    private function ensureSourceItemExistsInLinkedSuratJalan(TandaTerima $tandaTerima, PenjualanItem $sourceItem): void
    {
        $this->syncLinkedSuratJalanItems($tandaTerima);

        $suratJalan = SuratJalan::query()
            ->where('nomor_surat_jalan', $tandaTerima->nomor_surat_jalan)
            ->first();

        if ($suratJalan === null) {
            throw ValidationException::withMessages([
                'nomor_surat_jalan' => 'Surat jalan untuk tanda terima tidak ditemukan.',
            ]);
        }

        $exists = $suratJalan->items()
            ->where('penjualan_item_id', $sourceItem->id)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'penjualan_item_id' => 'Item tanda terima harus berasal dari item surat jalan yang terkait.',
            ]);
        }
    }

    private function syncLinkedSuratJalanItems(TandaTerima $tandaTerima): void
    {
        $suratJalan = SuratJalan::query()
            ->where('nomor_surat_jalan', $tandaTerima->nomor_surat_jalan)
            ->first();

        if ($suratJalan === null) {
            return;
        }

        $this->syncSuratJalanItemsFromPenjualan($suratJalan);

        $tandaTerima->items()->delete();

        $suratJalan->items()
            ->orderBy('id')
            ->get()
            ->each(function ($item) use ($tandaTerima): void {
                $tandaTerima->items()->create([
                    'penjualan_item_id' => $item->penjualan_item_id,
                    'nama_barang' => $item->nama_barang,
                    'qty' => $item->qty,
                    'satuan' => $item->satuan,
                    'keterangan' => $item->keterangan,
                ]);
            });
    }

    private function syncSuratJalanItemsFromPenjualan(SuratJalan $suratJalan): void
    {
        if ($suratJalan->tanggal === null) {
            $suratJalan->items()->delete();

            return;
        }

        $sourceItems = $this->queryMatchingPenjualan($suratJalan)
            ->flatMap(fn (Penjualan $penjualan) => $this->resolvePenjualanSourceItems($penjualan))
            ->values();

        $existingItems = $suratJalan->items()
            ->get()
            ->keyBy(fn ($item) => $this->buildSourceKey(
                $item->penjualan_item_id,
                $item->nama_barang,
                $item->qty,
                $item->satuan
            ));

        $suratJalan->items()->delete();

        foreach ($sourceItems as $sourceItem) {
            $currentItem = $existingItems->get($this->buildSourceKey(
                $sourceItem['penjualan_item_id'],
                $sourceItem['nama_barang'],
                $sourceItem['qty'],
                $sourceItem['satuan']
            ));

            $suratJalan->items()->create([
                'penjualan_item_id' => $sourceItem['penjualan_item_id'],
                'nama_barang' => $sourceItem['nama_barang'],
                'qty' => $sourceItem['qty'],
                'satuan' => $sourceItem['satuan'],
                'keterangan' => $currentItem?->keterangan,
            ]);
        }
    }

    private function queryMatchingPenjualan(SuratJalan $suratJalan)
    {
        $namaSppg = $suratJalan->relationLoaded('sppg')
            ? $suratJalan->sppg?->nama_sppg
            : $suratJalan->sppg()->value('nama_sppg');

        return Penjualan::query()
            ->with('items')
            ->whereDate('tanggal', $suratJalan->tanggal)
            ->when($namaSppg, function ($query, string $currentNamaSppg): void {
                $query->whereHas('orderPenawaran', function ($orderQuery) use ($currentNamaSppg): void {
                    $orderQuery->where('nama_pembeli', $currentNamaSppg);
                });
            })
            ->orderBy('id')
            ->get();
    }

    private function resolvePenjualanSourceItems(Penjualan $penjualan): Collection
    {
        if ($penjualan->items->isNotEmpty()) {
            return $penjualan->items->map(fn ($item): array => [
                'penjualan_item_id' => $item->id,
                'nama_barang' => $item->nama_barang,
                'qty' => $item->qty,
                'satuan' => $item->satuan,
            ]);
        }

        if ($penjualan->order_penawaran_id === null) {
            return collect();
        }

        return OrderPenawaranItem::query()
            ->where('order_penawaran_id', $penjualan->order_penawaran_id)
            ->orderBy('id')
            ->get()
            ->map(fn (OrderPenawaranItem $item): array => [
                'penjualan_item_id' => null,
                'nama_barang' => $item->nama_barang,
                'qty' => $item->qty,
                'satuan' => $item->satuan,
            ]);
    }

    private function buildSourceKey(
        ?int $penjualanItemId,
        string $namaBarang,
        string|float|int $qty,
        ?string $satuan
    ): string {
        return implode('|', [
            $penjualanItemId ?? 'null',
            mb_strtolower(trim($namaBarang)),
            number_format((float) $qty, 2, '.', ''),
            mb_strtolower(trim((string) $satuan)),
        ]);
    }
}
