<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DaftarPembelanjaan;
use App\Models\OrderPenawaran;
use App\Models\OrderPenawaranItem;
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
            $record = DaftarPembelanjaan::query()->create($payload);

            $this->copyOrderPenawaranItemsByDate($record);

            return $record;
        });

        return response()->json([
            'message' => 'Daftar pembelanjaan berhasil ditambahkan.',
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

    private function copyOrderPenawaranItemsByDate(DaftarPembelanjaan $record): void
    {
        OrderPenawaran::query()
            ->whereDate('tanggal_pesan', $record->tanggal_pesan)
            ->with('items')
            ->get()
            ->flatMap(fn (OrderPenawaran $orderPenawaran) => $orderPenawaran->items)
            ->each(function (OrderPenawaranItem $item) use ($record): void {
                $record->items()->create([
                    'produk_id' => $item->produk_id,
                    'kategori_id' => $item->kategori_id,
                    'supplier_id' => null,
                    'nama_barang' => $item->nama_barang,
                    'qty' => $item->qty,
                    'satuan' => $item->satuan,
                    'stok' => 0,
                    'kebutuhan' => $item->qty,
                    'nama_supplier' => '',
                ]);
            });
    }
}
