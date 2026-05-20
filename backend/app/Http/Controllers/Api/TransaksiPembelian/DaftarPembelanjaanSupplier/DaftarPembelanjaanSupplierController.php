<?php

namespace App\Http\Controllers\Api\TransaksiPembelian\DaftarPembelanjaanSupplier;

use App\Http\Controllers\Controller;
use App\Models\TransaksiPembelian\DaftarPembelanjaan;
use App\Models\TransaksiPembelian\DaftarPembelanjaanItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DaftarPembelanjaanSupplierController extends Controller
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
            ->whereHas('items', fn ($query) => $query->whereNotNull('supplier_id'))
            ->withCount([
                'items as supplier_count' => fn ($query) => $query->selectRaw('COUNT(DISTINCT supplier_id)')->whereNotNull('supplier_id'),
                'items as item_count' => fn ($query) => $query->whereNotNull('supplier_id'),
            ])
            ->when($tanggalPesan, fn ($query, string $tanggal) => $query->whereDate('tanggal_pesan', $tanggal))
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data daftar pembelanjaan supplier berhasil diambil.',
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

    public function show(DaftarPembelanjaan $daftarPembelanjaan): JsonResponse
    {
        $filters = request()->validate([
            'supplier_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $currentPage = max((int) request()->query('page', 1), 1);
        $perPage = $filters['per_page'] ?? 10;

        $daftarPembelanjaan->load(['items.produk', 'items.kategori', 'items.supplier']);

        $supplierGroups = $daftarPembelanjaan->items
            ->filter(fn (DaftarPembelanjaanItem $item) => $item->supplier !== null)
            ->groupBy('supplier_id')
            ->map(function ($items): array {
                /** @var DaftarPembelanjaanItem $firstItem */
                $firstItem = $items->first();

                return [
                    'supplier' => [
                        'id' => $firstItem->supplier->id,
                        'nama' => $firstItem->supplier->nama,
                        'alamat' => $firstItem->supplier->alamat,
                        'no_telp' => $firstItem->supplier->no_telp,
                        'kategori' => $firstItem->supplier->kategori,
                    ],
                    'items' => $items->map(fn (DaftarPembelanjaanItem $item): array => $this->transformItem($item))->values()->all(),
                ];
            })
            ->values();

        $selectedSupplierId = (int) ($filters['supplier_id'] ?? ($supplierGroups->first()['supplier']['id'] ?? 0));

        $selectedGroup = $supplierGroups
            ->first(fn (array $group): bool => (int) $group['supplier']['id'] === $selectedSupplierId);

        if ($selectedGroup === null) {
            $selectedSupplierId = (int) ($supplierGroups->first()['supplier']['id'] ?? 0);
            $selectedGroup = $supplierGroups->first();
        }

        $selectedItems = collect($selectedGroup['items'] ?? [])->values();

        $paginatedItems = new LengthAwarePaginator(
            $selectedItems->forPage($currentPage, $perPage)->values()->all(),
            $selectedItems->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        return response()->json([
            'message' => 'Detail daftar pembelanjaan supplier berhasil diambil.',
            'data' => [
                'id' => $daftarPembelanjaan->id,
                'tanggal_pesan' => $daftarPembelanjaan->tanggal_pesan,
                'suppliers' => $supplierGroups->map(fn (array $group): array => [
                    'supplier' => $group['supplier'],
                ])->values()->all(),
                'selected_supplier_id' => $selectedSupplierId ?: null,
                'items' => $paginatedItems->items(),
                'meta' => [
                    'current_page' => $paginatedItems->currentPage(),
                    'last_page' => $paginatedItems->lastPage(),
                    'per_page' => $paginatedItems->perPage(),
                    'total' => $paginatedItems->total(),
                    'from' => $paginatedItems->firstItem(),
                    'to' => $paginatedItems->lastItem(),
                ],
            ],
        ]);
    }

    /**
     * @return array{id:int,produk_id:int|null,kategori_id:int|null,supplier_id:int|null,nama_barang:string|null,qty:string|float|int|null,satuan:string|null,stok:string|float|int|null,kebutuhan:string|float|int|null,nama_supplier:string|null,produk:array{id:int,sku:string|null,nama:string|null,kategori:string|null,satuan:string|null}|null,kategori:array{id:int,kode:string|null,nama_satuan:string|null}|null,supplier:array{id:int,nama:string|null,alamat:string|null,no_telp:string|null,kategori:string|null}|null}
     */
    private function transformItem(DaftarPembelanjaanItem $item): array
    {
        return [
            'id' => $item->id,
            'produk_id' => $item->produk_id,
            'kategori_id' => $item->kategori_id,
            'supplier_id' => $item->supplier_id,
            'nama_barang' => $item->produk?->nama ?? $item->nama_barang,
            'qty' => $item->qty,
            'satuan' => $item->kategori?->nama_satuan ?? $item->satuan,
            'stok' => $item->stok,
            'kebutuhan' => $item->kebutuhan,
            'nama_supplier' => $item->nama_supplier,
            'produk' => $item->produk ? [
                'id' => $item->produk->id,
                'sku' => $item->produk->sku,
                'nama' => $item->produk->nama,
                'kategori' => $item->produk->kategori,
                'satuan' => $item->produk->satuan,
            ] : null,
            'kategori' => $item->kategori ? [
                'id' => $item->kategori->id,
                'kode' => $item->kategori->kode,
                'nama_satuan' => $item->kategori->nama_satuan,
            ] : null,
            'supplier' => $item->supplier ? [
                'id' => $item->supplier->id,
                'nama' => $item->supplier->nama,
                'alamat' => $item->supplier->alamat,
                'no_telp' => $item->supplier->no_telp,
                'kategori' => $item->supplier->kategori,
            ] : null,
        ];
    }
}
