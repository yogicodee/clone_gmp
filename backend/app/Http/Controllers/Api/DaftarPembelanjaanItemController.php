<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DaftarPembelanjaan;
use App\Models\DaftarPembelanjaanItem;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
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

        $items = $daftarPembelanjaan->items()
            ->with(['produk', 'kategori', 'supplier'])
            ->when($search, function ($query, string $keyword) {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('nama_barang', 'like', '%'.$keyword.'%')
                        ->orWhere('nama_supplier', 'like', '%'.$keyword.'%');
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

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
