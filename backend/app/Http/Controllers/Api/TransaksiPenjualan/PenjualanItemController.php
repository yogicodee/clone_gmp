<?php

namespace App\Http\Controllers\Api\TransaksiPenjualan;

use App\Http\Controllers\Controller;
use App\Models\TransaksiPembelian\OrderPenawaranItem;
use App\Models\TransaksiPenjualan\Penjualan;
use App\Models\TransaksiPenjualan\PenjualanItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenjualanItemController extends Controller
{
    public function index(Request $request, Penjualan $penjualan): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
        ]);

        $search = $filters['search'] ?? null;

        $items = $penjualan->items()
            ->with('gudang')
            ->when($search, function ($query, string $keyword): void {
                $query->where('nama_barang', 'like', '%'.$keyword.'%');
            })
            ->orderBy('id')
            ->get();

        return response()->json([
            'message' => 'Data item penjualan berhasil diambil.',
            'data' => $items,
        ]);
    }

    public function store(Request $request, Penjualan $penjualan): JsonResponse
    {
        $payload = $this->validatePayload($request, $penjualan);

        $item = DB::transaction(function () use ($penjualan, $payload): PenjualanItem {
            $item = $this->persistItem(new PenjualanItem(), $penjualan, $payload);
            $this->refreshParentTotal($penjualan);

            return $item;
        });

        return response()->json([
            'message' => 'Item penjualan berhasil ditambahkan.',
            'data' => $item->load('gudang'),
        ], 201);
    }

    public function show(Penjualan $penjualan, PenjualanItem $item): JsonResponse
    {
        $this->ensureItemBelongsToPenjualan($penjualan, $item);

        return response()->json([
            'message' => 'Detail item penjualan berhasil diambil.',
            'data' => $item->load('gudang'),
        ]);
    }

    public function update(Request $request, Penjualan $penjualan, PenjualanItem $item): JsonResponse
    {
        $this->ensureItemBelongsToPenjualan($penjualan, $item);
        $payload = $this->validatePayload($request, $penjualan);

        $item = DB::transaction(function () use ($item, $penjualan, $payload): PenjualanItem {
            $updatedItem = $this->persistItem($item, $penjualan, $payload);
            $this->refreshParentTotal($penjualan);

            return $updatedItem;
        });

        return response()->json([
            'message' => 'Item penjualan berhasil diperbarui.',
            'data' => $item->load('gudang'),
        ]);
    }

    public function destroy(Penjualan $penjualan, PenjualanItem $item): JsonResponse
    {
        $this->ensureItemBelongsToPenjualan($penjualan, $item);

        DB::transaction(function () use ($penjualan, $item): void {
            $item->delete();
            $this->refreshParentTotal($penjualan);
        });

        return response()->json([
            'message' => 'Item penjualan berhasil dihapus.',
        ]);
    }

    public function opsiBarang(Penjualan $penjualan): JsonResponse
    {
        $items = OrderPenawaranItem::query()
            ->with('orderPenawaran:id,tanggal_dikirim')
            ->whereHas('orderPenawaran', function ($query) use ($penjualan): void {
                $query->whereDate('tanggal_dikirim', $penjualan->tanggal);
            })
            ->orderBy('nama_barang')
            ->get();

        $options = $items
            ->groupBy(function (OrderPenawaranItem $item): string {
                return implode('|', [
                    $item->produk_id ?? 'null',
                    mb_strtolower(trim($item->nama_barang)),
                    (string) $item->harga_satuan,
                    mb_strtolower(trim((string) $item->satuan)),
                ]);
            })
            ->map(function (Collection $group): array {
                /** @var OrderPenawaranItem $item */
                $item = $group->first();

                return [
                    'order_penawaran_item_id' => $item->id,
                    'produk_id' => $item->produk_id,
                    'nama_barang' => $item->nama_barang,
                    'harga_satuan' => $item->harga_satuan,
                    'satuan' => $item->satuan,
                ];
            })
            ->values();

        return response()->json([
            'message' => 'Opsi barang penjualan berhasil diambil.',
            'data' => $options,
        ]);
    }

    private function validatePayload(Request $request, Penjualan $penjualan): array
    {
        $payload = $request->validate([
            'order_penawaran_item_id' => ['required', 'integer', 'exists:order_penawaran_items,id'],
            'gudang_id' => ['required', 'integer', 'exists:gudang,id'],
            'qty' => ['required', 'numeric', 'gt:0'],
        ]);

        $sourceItem = OrderPenawaranItem::query()
            ->with('orderPenawaran:id,tanggal_dikirim')
            ->findOrFail($payload['order_penawaran_item_id']);

        if ($sourceItem->orderPenawaran === null || $sourceItem->orderPenawaran->tanggal_dikirim !== $penjualan->tanggal->format('Y-m-d')) {
            throw ValidationException::withMessages([
                'order_penawaran_item_id' => 'Barang hanya boleh diambil dari order penawaran dengan tanggal kirim yang sama.',
            ]);
        }

        $payload['_source_item'] = $sourceItem;

        return $payload;
    }

    private function persistItem(PenjualanItem $item, Penjualan $penjualan, array $payload): PenjualanItem
    {
        /** @var OrderPenawaranItem $sourceItem */
        $sourceItem = $payload['_source_item'];

        $item->fill([
            'penjualan_id' => $penjualan->id,
            'order_penawaran_item_id' => $sourceItem->id,
            'produk_id' => $sourceItem->produk_id,
            'gudang_id' => $payload['gudang_id'],
            'nama_barang' => $sourceItem->nama_barang,
            'qty' => $payload['qty'],
            'satuan' => $sourceItem->satuan,
            'harga_satuan' => $sourceItem->harga_satuan,
            'total_harga' => (float) $payload['qty'] * (float) $sourceItem->harga_satuan,
        ]);

        $item->save();

        return $item->fresh(['gudang']);
    }

    private function refreshParentTotal(Penjualan $penjualan): void
    {
        $penjualan->update([
            'total_harga' => $penjualan->items()->sum('total_harga'),
        ]);
    }

    private function ensureItemBelongsToPenjualan(Penjualan $penjualan, PenjualanItem $item): void
    {
        abort_if($item->penjualan_id !== $penjualan->id, 404);
    }
}
