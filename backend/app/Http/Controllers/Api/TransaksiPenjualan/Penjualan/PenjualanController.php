<?php

namespace App\Http\Controllers\Api\TransaksiPenjualan\Penjualan;

use App\Http\Controllers\Controller;
use App\Models\TransaksiPembelian\OrderPenawaran;
use App\Models\TransaksiPenjualan\Penjualan;
use App\Support\CacheInvalidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PenjualanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'kode_penjualan', 'tanggal', 'total_harga', 'status'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'tanggal';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 10;

        $records = Penjualan::query()
            ->with('orderPenawaran:id,tanggal_dikirim,nama_pembeli,keterangan')
            ->when($search, function ($query, string $keyword): void {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('kode_penjualan', 'like', '%'.$keyword.'%')
                        ->orWhere('status', 'like', '%'.$keyword.'%')
                        ->orWhereHas('orderPenawaran', function ($orderQuery) use ($keyword): void {
                            $orderQuery
                                ->where('nama_pembeli', 'like', '%'.$keyword.'%')
                                ->orWhere('keterangan', 'like', '%'.$keyword.'%');
                        });
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data penjualan berhasil diambil.',
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

        $orders = OrderPenawaran::query()
            ->with('items')
            ->whereDate('tanggal_dikirim', $payload['tanggal'])
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {
            throw ValidationException::withMessages([
                'tanggal' => 'Belum ada order penawaran dengan tanggal kirim tersebut.',
            ]);
        }

        $records = DB::transaction(function () use ($orders): array {
            return $orders
                ->map(fn (OrderPenawaran $order) => $this->syncFromOrderPenawaran($order))
                ->all();
        });
        CacheInvalidation::flushFinancialCaches();

        return response()->json([
            'message' => 'Data penjualan berhasil disinkronkan dari order penawaran.',
            'data' => $records,
        ], 201);
    }

    public function show(Penjualan $penjualan): JsonResponse
    {
        $penjualan->load([
            'orderPenawaran',
            'items.gudang',
            'items.orderPenawaranItem.orderPenawaran',
        ]);

        return response()->json([
            'message' => 'Detail penjualan berhasil diambil.',
            'data' => $penjualan,
        ]);
    }

    public function update(Request $request, Penjualan $penjualan): JsonResponse
    {
        $payload = $this->validatePayload($request, $penjualan);
        $penjualan->update($payload);
        CacheInvalidation::flushFinancialCaches();

        return response()->json([
            'message' => 'Data penjualan berhasil diperbarui.',
            'data' => $penjualan->fresh(),
        ]);
    }

    public function destroy(Penjualan $penjualan): JsonResponse
    {
        $penjualan->delete();
        CacheInvalidation::flushFinancialCaches();

        return response()->json([
            'message' => 'Data penjualan berhasil dihapus.',
        ]);
    }

    private function validatePayload(Request $request, ?Penjualan $penjualan = null): array
    {
        $payload = $request->validate([
            'order_penawaran_id' => [
                'nullable',
                'integer',
                'exists:order_penawaran,id',
                Rule::unique('penjualan', 'order_penawaran_id')->ignore($penjualan?->id),
            ],
            'kode_penjualan' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('penjualan', 'kode_penjualan')->ignore($penjualan?->id),
            ],
            'tanggal' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['draft', 'selesai', 'batal'])],
        ]);

        if (! empty($payload['order_penawaran_id'])) {
            $orderPenawaran = OrderPenawaran::query()->findOrFail($payload['order_penawaran_id']);

            if ($orderPenawaran->tanggal_dikirim === null) {
                throw ValidationException::withMessages([
                    'order_penawaran_id' => 'Order penawaran harus memiliki tanggal kirim untuk menjadi penjualan.',
                ]);
            }

            $payload['tanggal'] = $orderPenawaran->tanggal_dikirim;
            $payload['kode_penjualan'] = $payload['kode_penjualan'] ?: $this->generateKodePenjualan($orderPenawaran->id);
            $payload['total_harga'] = $orderPenawaran->items()->get()
                ->sum(fn ($item) => (float) $item->qty * (float) $item->harga_satuan);
        } else {
            $payload['kode_penjualan'] = $payload['kode_penjualan'] ?? null;

            if ($payload['kode_penjualan'] === null || empty($payload['tanggal'])) {
                throw ValidationException::withMessages([
                    'order_penawaran_id' => 'Pilih sumber order penawaran, atau isi kode penjualan dan tanggal manual.',
                ]);
            }
        }

        return $payload;
    }

    private function syncFromOrderPenawaran(OrderPenawaran $order): Penjualan
    {
        $existing = Penjualan::query()
            ->where('order_penawaran_id', $order->id)
            ->first();

        return Penjualan::query()->updateOrCreate(
            ['order_penawaran_id' => $order->id],
            [
                'kode_penjualan' => $existing?->kode_penjualan ?: $this->generateKodePenjualan($order->id),
                'tanggal' => $order->tanggal_dikirim,
                'status' => $existing?->status ?: 'draft',
                'total_harga' => $order->items->sum(
                    fn ($item) => (float) $item->qty * (float) $item->harga_satuan
                ),
            ]
        );
    }

    private function generateKodePenjualan(int $orderPenawaranId): string
    {
        return 'TRX-OP-'.str_pad((string) $orderPenawaranId, 4, '0', STR_PAD_LEFT);
    }
}
