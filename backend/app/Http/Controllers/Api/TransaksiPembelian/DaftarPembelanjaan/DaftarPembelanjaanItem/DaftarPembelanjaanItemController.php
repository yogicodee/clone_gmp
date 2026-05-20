<?php

namespace App\Http\Controllers\Api\TransaksiPembelian\DaftarPembelanjaan\DaftarPembelanjaanItem;

use App\Http\Controllers\Controller;
use App\Models\MasterData\Supplier;
use App\Models\TransaksiPembelian\DaftarPembelanjaan;
use App\Models\TransaksiPembelian\DaftarPembelanjaanItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DaftarPembelanjaanItemController extends Controller
{
    public function index(Request $request, DaftarPembelanjaan $daftarPembelanjaan): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'nama_barang', 'qty', 'satuan', 'stok', 'kebutuhan', 'nama_supplier'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'nama_barang';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;
        $currentPage = max((int) $request->query('page', 1), 1);

        $groupedItems = $daftarPembelanjaan->items()
            ->with(['produk', 'kategori', 'supplier'])
            ->get()
            ->groupBy(fn (DaftarPembelanjaanItem $item): string => mb_strtolower(trim($item->nama_barang)))
            ->map(function ($items) {
                /** @var DaftarPembelanjaanItem $firstItem */
                $firstItem = $items->first();

                $aggregatedQty = $items->sum(fn (DaftarPembelanjaanItem $item): float => (float) $item->qty);

                return [
                    'id' => $firstItem->id,
                    'produk_id' => $firstItem->produk_id,
                    'kategori_id' => $firstItem->kategori_id,
                    'supplier_id' => $firstItem->supplier_id,
                    'nama_barang' => $firstItem->nama_barang,
                    'qty' => $aggregatedQty,
                    'satuan' => $firstItem->satuan,
                    'stok' => $firstItem->stok,
                    'kebutuhan' => $firstItem->kebutuhan,
                    'nama_supplier' => $firstItem->nama_supplier,
                    'keterangan' => $firstItem->keterangan,
                    'produk' => $firstItem->produk,
                    'kategori' => $firstItem->kategori,
                    'supplier' => $firstItem->supplier,
                ];
            })
            ->when($search, function ($collection, string $keyword) {
                $normalizedKeyword = mb_strtolower(trim($keyword));

                return $collection->filter(function (array $item) use ($normalizedKeyword): bool {
                    return str_contains(mb_strtolower($item['nama_barang']), $normalizedKeyword)
                        || str_contains(mb_strtolower($item['nama_supplier'] ?? ''), $normalizedKeyword);
                });
            })
            ->sort(function (array $first, array $second) use ($sortField, $sortOrder): int {
                $firstValue = $first[$sortField] ?? null;
                $secondValue = $second[$sortField] ?? null;

                if (in_array($sortField, ['qty', 'stok', 'kebutuhan'], true)) {
                    $comparison = (float) $firstValue <=> (float) $secondValue;

                    return $sortOrder === 'asc' ? $comparison : -$comparison;
                }

                $comparison = strnatcasecmp((string) $firstValue, (string) $secondValue);

                return $sortOrder === 'asc' ? $comparison : -$comparison;
            })
            ->values();

        $items = new LengthAwarePaginator(
            $groupedItems->forPage($currentPage, $perPage)->values()->all(),
            $groupedItems->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return response()->json([
            'message' => 'Data detail pembelanjaan berhasil diambil.',
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ],
        ]);
    }

    public function store(Request $request, DaftarPembelanjaan $daftarPembelanjaan): JsonResponse
    {
        $payload = $this->validatePayload($request);
        $payload['daftar_pembelanjaan_id'] = $daftarPembelanjaan->id;
        $payload = $this->normalizePayload($payload);

        $item = DaftarPembelanjaanItem::query()->create($payload);

        return response()->json([
            'message' => 'Detail pembelanjaan berhasil ditambahkan.',
            'data' => $item->load(['produk', 'kategori', 'supplier']),
        ], 201);
    }

    public function show(DaftarPembelanjaan $daftarPembelanjaan, DaftarPembelanjaanItem $item): JsonResponse
    {
        $this->ensureItemBelongsToRecord($daftarPembelanjaan, $item);

        return response()->json([
            'message' => 'Detail item pembelanjaan berhasil diambil.',
            'data' => $item->load(['produk', 'kategori', 'supplier']),
        ]);
    }

    public function update(Request $request, DaftarPembelanjaan $daftarPembelanjaan, DaftarPembelanjaanItem $item): JsonResponse
    {
        $this->ensureItemBelongsToRecord($daftarPembelanjaan, $item);

        $payload = $this->validatePayload($request);
        $payload = $this->normalizePayload($payload);
        $item->update($payload);

        return response()->json([
            'message' => 'Detail pembelanjaan berhasil diperbarui.',
            'data' => $item->fresh()->load(['produk', 'kategori', 'supplier']),
        ]);
    }

    public function destroy(DaftarPembelanjaan $daftarPembelanjaan, DaftarPembelanjaanItem $item): JsonResponse
    {
        $this->ensureItemBelongsToRecord($daftarPembelanjaan, $item);
        $item->delete();

        return response()->json([
            'message' => 'Detail pembelanjaan berhasil dihapus.',
        ]);
    }

    /**
     * @return array{nama_barang:string, qty:numeric-string|float|int, satuan:string, stok:numeric-string|float|int, kebutuhan:numeric-string|float|int, nama_supplier:string}
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'produk_id' => ['nullable', 'integer', 'exists:produk,id'],
            'kategori_id' => ['nullable', 'integer', 'exists:kategori,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:supplier,id'],
            'nama_barang' => ['required', 'string', 'max:100'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'satuan' => ['required', 'string', 'max:50'],
            'stok' => ['required', 'numeric', 'min:0'],
            'kebutuhan' => ['required', 'numeric', 'min:0'],
            'nama_supplier' => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function normalizePayload(array $payload): array
    {
        if (! empty($payload['supplier_id'])) {
            $payload['nama_supplier'] = Supplier::query()
                ->whereKey($payload['supplier_id'])
                ->value('nama') ?? ($payload['nama_supplier'] ?? '');
        }

        $payload['nama_supplier'] ??= '';

        return $payload;
    }

    private function ensureItemBelongsToRecord(DaftarPembelanjaan $daftarPembelanjaan, DaftarPembelanjaanItem $item): void
    {
        abort_if($item->daftar_pembelanjaan_id !== $daftarPembelanjaan->id, 404);
    }
}
