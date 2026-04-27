<?php

namespace App\Http\Controllers\Api\TransaksiPenjualan\InvoicePenjualan;

use App\Http\Controllers\Controller;
use App\Models\TransaksiPenjualan\InvoicePenjualan;
use App\Models\TransaksiPenjualan\Penjualan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvoicePenjualanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['id', 'nomor_invoice', 'penjualan_id', 'tanggal_invoice', 'total_tagihan', 'status_pembayaran'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = $filters['search'] ?? null;
        $sortField = $filters['sort_field'] ?? 'tanggal_invoice';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 10;

        $records = InvoicePenjualan::query()
            ->with('penjualan:id,kode_penjualan')
            ->when($search, function ($query, string $keyword): void {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('nomor_invoice', 'like', '%'.$keyword.'%')
                        ->orWhere('status_pembayaran', 'like', '%'.$keyword.'%')
                        ->orWhereHas('penjualan', function ($penjualanQuery) use ($keyword): void {
                            $penjualanQuery->where('kode_penjualan', 'like', '%'.$keyword.'%');
                        });
                });
            })
            ->orderBy($sortField, $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Data invoice penjualan berhasil diambil.',
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
        $record = InvoicePenjualan::query()->create($payload);

        return response()->json([
            'message' => 'Invoice penjualan berhasil ditambahkan.',
            'data' => $record->load('penjualan:id,kode_penjualan'),
        ], 201);
    }

    public function show(InvoicePenjualan $invoicePenjualan): JsonResponse
    {
        $invoicePenjualan->load('penjualan:id,kode_penjualan,tanggal,total_harga,status');

        return response()->json([
            'message' => 'Detail invoice penjualan berhasil diambil.',
            'data' => $invoicePenjualan,
        ]);
    }

    public function update(Request $request, InvoicePenjualan $invoicePenjualan): JsonResponse
    {
        $payload = $this->validatePayload($request, $invoicePenjualan);
        $invoicePenjualan->update($payload);

        return response()->json([
            'message' => 'Invoice penjualan berhasil diperbarui.',
            'data' => $invoicePenjualan->fresh('penjualan:id,kode_penjualan'),
        ]);
    }

    public function destroy(InvoicePenjualan $invoicePenjualan): JsonResponse
    {
        $invoicePenjualan->delete();

        return response()->json([
            'message' => 'Invoice penjualan berhasil dihapus.',
        ]);
    }

    private function validatePayload(Request $request, ?InvoicePenjualan $invoicePenjualan = null): array
    {
        $payload = $request->validate([
            'nomor_invoice' => [
                'required',
                'string',
                'max:50',
                Rule::unique('invoice_penjualan', 'nomor_invoice')->ignore($invoicePenjualan?->id),
            ],
            'penjualan_id' => ['required', 'integer', 'exists:penjualan,id'],
            'tanggal_invoice' => ['required', 'date'],
            'total_tagihan' => ['nullable', 'numeric', 'min:0'],
            'status_pembayaran' => ['required', Rule::in(['lunas', 'belum lunas'])],
        ]);

        if (! array_key_exists('total_tagihan', $payload) || $payload['total_tagihan'] === null) {
            $penjualan = Penjualan::query()->findOrFail($payload['penjualan_id']);
            $payload['total_tagihan'] = $penjualan->total_harga;
        } else {
            $penjualan = Penjualan::query()->findOrFail($payload['penjualan_id']);
        }

        if ($penjualan->status !== 'selesai') {
            throw ValidationException::withMessages([
                'penjualan_id' => 'Invoice hanya boleh dibuat untuk penjualan yang sudah selesai.',
            ]);
        }

        $duplicateInvoice = InvoicePenjualan::query()
            ->where('penjualan_id', $payload['penjualan_id'])
            ->when($invoicePenjualan !== null, fn ($query) => $query->where('id', '!=', $invoicePenjualan->id))
            ->exists();

        if ($duplicateInvoice) {
            throw ValidationException::withMessages([
                'penjualan_id' => 'Setiap penjualan hanya boleh memiliki satu invoice.',
            ]);
        }

        return $payload;
    }
}
