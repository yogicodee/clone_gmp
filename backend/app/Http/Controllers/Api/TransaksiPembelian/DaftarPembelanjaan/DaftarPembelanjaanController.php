<?php

namespace App\Http\Controllers\Api\TransaksiPembelian\DaftarPembelanjaan;

use App\Http\Controllers\Controller;
use App\Models\TransaksiPembelian\DaftarPembelanjaan;
use App\Models\TransaksiPembelian\OrderPenawaran;
use App\Models\TransaksiPembelian\OrderPenawaranItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DaftarPembelanjaanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'tanggal_pesan' => ['nullable', 'date'],
            'sort_field' => ['nullable', Rule::in(['id', 'tanggal_pesan'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $tanggalPesan = $filters['tanggal_pesan'] ?? null;
        $sortField = $filters['sort_field'] ?? 'tanggal_pesan';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 10;

        $records = DaftarPembelanjaan::query()
            ->when($tanggalPesan, fn ($query, string $tanggal) => $query->whereDate('tanggal_pesan', $tanggal))
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data daftar pembelanjaan berhasil diambil.',
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
        $payload = $this->validatePayload($request);

        $record = DB::transaction(function () use ($payload): DaftarPembelanjaan {
            $record = DaftarPembelanjaan::query()->firstOrCreate([
                'tanggal_pesan' => $payload['tanggal_pesan'],
            ]);

            $this->syncOrderPenawaranItemsByDate($record);

            return $record;
        });

        return response()->json([
            'message' => 'Daftar pembelanjaan berhasil disimpan.',
            'data' => $record->load('items'),
        ], 201);
    }

    public function show(DaftarPembelanjaan $daftarPembelanjaan): JsonResponse
    {
        $daftarPembelanjaan->load(['items.produk', 'items.kategori', 'items.supplier']);

        return response()->json([
            'message' => 'Detail daftar pembelanjaan berhasil diambil.',
            'data' => $daftarPembelanjaan,
        ]);
    }

    public function update(Request $request, DaftarPembelanjaan $daftarPembelanjaan): JsonResponse
    {
        $payload = $this->validatePayload($request);

        $daftarPembelanjaan->update($payload);

        return response()->json([
            'message' => 'Daftar pembelanjaan berhasil diperbarui.',
            'data' => $daftarPembelanjaan->fresh(),
        ]);
    }

    public function destroy(DaftarPembelanjaan $daftarPembelanjaan): JsonResponse
    {
        $daftarPembelanjaan->delete();

        return response()->json([
            'message' => 'Daftar pembelanjaan berhasil dihapus.',
        ]);
    }

    /**
     * @return array{tanggal_pesan: string}
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'tanggal_pesan' => ['required', 'date'],
        ]);
    }

    private function syncOrderPenawaranItemsByDate(DaftarPembelanjaan $record): void
    {
        $record->items()->delete();

        $orders = OrderPenawaran::query()
            ->whereDate('tanggal_pesan', $record->tanggal_pesan)
            ->with('items')
            ->get();

        $groupedItems = $orders
            ->flatMap(function (OrderPenawaran $orderPenawaran) {
                return $orderPenawaran->items;
            })
            ->groupBy(function (OrderPenawaranItem $item): string {
                return implode('|', [
                    $item->produk_id ?? 'null',
                    $item->kategori_id ?? 'null',
                    mb_strtolower(trim($item->nama_barang)),
                    mb_strtolower(trim($item->satuan)),
                ]);
            });

        foreach ($groupedItems as $items) {
            /** @var OrderPenawaranItem $firstItem */
            $firstItem = $items->first();
            $totalQty = $items->sum(function (OrderPenawaranItem $item): float {
                return (float) $item->qty;
            });

            $record->items()->create([
                'produk_id' => $firstItem->produk_id,
                'kategori_id' => $firstItem->kategori_id,
                'supplier_id' => null,
                'nama_barang' => $firstItem->nama_barang,
                'qty' => $totalQty,
                'satuan' => $firstItem->satuan,
                'stok' => 0,
                'kebutuhan' => $totalQty,
                'nama_supplier' => '',
            ]);
        }
    }
}
