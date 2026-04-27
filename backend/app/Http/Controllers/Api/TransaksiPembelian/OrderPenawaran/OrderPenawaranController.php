<?php

namespace App\Http\Controllers\Api\TransaksiPembelian\OrderPenawaran;

use App\Http\Controllers\Controller;
use App\Models\TransaksiPembelian\OrderPenawaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderPenawaranController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'tanggal_pesan', 'tanggal_dikirim', 'nama_pembeli', 'keterangan'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'tanggal_pesan';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 10;

        $orderPenawaran = OrderPenawaran::query()
            ->when($search, function ($query, string $keyword) {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('nama_pembeli', 'like', '%'.$keyword.'%')
                        ->orWhere('keterangan', 'like', '%'.$keyword.'%');
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data order penawaran berhasil diambil.',
            'data' => $orderPenawaran->items(),
            'meta' => [
                'current_page' => $orderPenawaran->currentPage(),
                'last_page' => $orderPenawaran->lastPage(),
                'per_page' => $orderPenawaran->perPage(),
                'total' => $orderPenawaran->total(),
                'from' => $orderPenawaran->firstItem(),
                'to' => $orderPenawaran->lastItem(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatePayload($request);

        $orderPenawaran = OrderPenawaran::query()->create($payload);

        return response()->json([
            'message' => 'Order penawaran berhasil ditambahkan.',
            'data' => $orderPenawaran,
        ], 201);
    }

    public function byTanggal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
        ]);

        $orders = OrderPenawaran::query()
            ->with('items')
            ->whereDate('tanggal_pesan', $validated['tanggal'])
            ->orderBy('tanggal_pesan')
            ->orderBy('id')
            ->get();

        return response()->json([
            'message' => 'Data order penawaran berdasarkan tanggal berhasil diambil.',
            'data' => $orders,
        ]);
    }

    public function show(OrderPenawaran $orderPenawaran): JsonResponse
    {
        $orderPenawaran->load('items');

        return response()->json([
            'message' => 'Detail order penawaran berhasil diambil.',
            'data' => $orderPenawaran,
        ]);
    }

    public function update(Request $request, OrderPenawaran $orderPenawaran): JsonResponse
    {
        $payload = $this->validatePayload($request);

        $orderPenawaran->update($payload);

        return response()->json([
            'message' => 'Order penawaran berhasil diperbarui.',
            'data' => $orderPenawaran->fresh(),
        ]);
    }

    public function destroy(OrderPenawaran $orderPenawaran): JsonResponse
    {
        $orderPenawaran->delete();

        return response()->json([
            'message' => 'Order penawaran berhasil dihapus.',
        ]);
    }

    /**
     * @return array{tanggal_pesan: string, tanggal_dikirim: string|null, nama_pembeli: string, keterangan: string|null}
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'tanggal_pesan' => ['required', 'date'],
            'tanggal_dikirim' => ['nullable', 'date', 'after_or_equal:tanggal_pesan'],
            'nama_pembeli' => ['required', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string'],
        ]);
    }
}
