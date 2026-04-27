<?php

namespace App\Http\Controllers\Api\TransaksiPembelian\OrderPenawaran\OrderPenawaranItem;

use App\Http\Controllers\Controller;
use App\Models\TransaksiPembelian\OrderPenawaran;
use App\Models\TransaksiPembelian\OrderPenawaranItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderPenawaranItemController extends Controller
{
    public function index(Request $request, OrderPenawaran $orderPenawaran): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'nama_barang', 'qty', 'satuan', 'harga_satuan', 'keterangan'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'nama_barang';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $perPage = $filters['per_page'] ?? 10;

        $items = $orderPenawaran->items()
            ->when($search, function ($query, string $keyword) {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('nama_barang', 'like', '%'.$keyword.'%')
                        ->orWhere('keterangan', 'like', '%'.$keyword.'%');
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data item order penawaran berhasil diambil.',
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

    public function store(Request $request, OrderPenawaran $orderPenawaran): JsonResponse
    {
        $payload = $this->validatePayload($request);
        $payload['order_penawaran_id'] = $orderPenawaran->id;

        $item = OrderPenawaranItem::query()->create($payload);

        return response()->json([
            'message' => 'Item order penawaran berhasil ditambahkan.',
            'data' => $item,
        ], 201);
    }

    public function show(OrderPenawaran $orderPenawaran, OrderPenawaranItem $item): JsonResponse
    {
        $this->ensureItemBelongsToOrder($orderPenawaran, $item);

        return response()->json([
            'message' => 'Detail item order penawaran berhasil diambil.',
            'data' => $item,
        ]);
    }

    public function update(Request $request, OrderPenawaran $orderPenawaran, OrderPenawaranItem $item): JsonResponse
    {
        $this->ensureItemBelongsToOrder($orderPenawaran, $item);

        $payload = $this->validatePayload($request);
        $item->update($payload);

        return response()->json([
            'message' => 'Item order penawaran berhasil diperbarui.',
            'data' => $item->fresh(),
        ]);
    }

    public function destroy(OrderPenawaran $orderPenawaran, OrderPenawaranItem $item): JsonResponse
    {
        $this->ensureItemBelongsToOrder($orderPenawaran, $item);
        $item->delete();

        return response()->json([
            'message' => 'Item order penawaran berhasil dihapus.',
        ]);
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'produk_id' => ['nullable', 'integer', 'exists:produk,id'],
            'kategori_id' => ['nullable', 'integer', 'exists:kategori,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:supplier,id'],
            'nama_barang' => ['required', 'string', 'max:100'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'satuan' => ['required', 'string', 'max:50'],
            'harga_satuan' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string'],
        ]);
    }

    private function ensureItemBelongsToOrder(OrderPenawaran $orderPenawaran, OrderPenawaranItem $item): void
    {
        abort_if($item->order_penawaran_id !== $orderPenawaran->id, 404);
    }
}
