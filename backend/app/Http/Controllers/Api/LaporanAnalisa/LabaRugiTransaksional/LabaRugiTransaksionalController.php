<?php

namespace App\Http\Controllers\Api\LaporanAnalisa\LabaRugiTransaksional;

use App\Http\Controllers\Controller;
use App\Support\CacheInvalidation;
use App\Models\KeuanganAkuntansi\Pemasukan;
use App\Models\KeuanganAkuntansi\Pengeluaran;
use App\Models\MasterData\Sppg;
use App\Models\TransaksiPenjualan\InvoicePenjualan;
use App\Models\TransaksiPenjualan\TandaTerima;
use App\Models\WarehouseSystem\WarehouseInbound;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LabaRugiTransaksionalController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tanggal_awal' => ['nullable', 'date'],
            'tanggal_akhir' => ['nullable', 'date'],
            'sppg_id' => ['nullable', 'integer', 'exists:sppg,id'],
        ]);

        $tanggalAwal = isset($validated['tanggal_awal'])
            ? Carbon::parse($validated['tanggal_awal'], 'Asia/Jakarta')->startOfDay()
            : Carbon::today('Asia/Jakarta')->startOfMonth();

        $tanggalAkhir = isset($validated['tanggal_akhir'])
            ? Carbon::parse($validated['tanggal_akhir'], 'Asia/Jakarta')->endOfDay()
            : Carbon::today('Asia/Jakarta')->endOfDay();

        if ($tanggalAwal->greaterThan($tanggalAkhir)) {
            [$tanggalAwal, $tanggalAkhir] = [$tanggalAkhir->copy()->startOfDay(), $tanggalAwal->copy()->endOfDay()];
        }

        $sppgId = $validated['sppg_id'] ?? null;
        $tanggalAwalString = $tanggalAwal->toDateString();
        $tanggalAkhirString = $tanggalAkhir->toDateString();
        $cacheKey = sprintf(
            'laba_rugi_transaksional:%s:%s:%s',
            $tanggalAwalString,
            $tanggalAkhirString,
            $sppgId ?? 'all'
        );

        $report = Cache::tags([CacheInvalidation::TAG_LABA_RUGI_TRANSAKSIONAL])->remember(
            $cacheKey,
            now()->addMinutes(5),
            function () use ($tanggalAwal, $tanggalAkhir, $tanggalAwalString, $tanggalAkhirString, $sppgId): array {
                $invoiceRows = $this->buildInvoiceRows($tanggalAwal, $tanggalAkhir, $sppgId);
                $pemasukanRows = $this->buildPemasukanRows($tanggalAwal, $tanggalAkhir);
                $pengeluaranPembelanjaanRows = $this->buildPengeluaranPembelanjaanRows($tanggalAwal, $tanggalAkhir);
                $pengeluaranRows = $this->buildPengeluaranRows($tanggalAwal, $tanggalAkhir);
                $totalPendapatanPenjualan = (float) $invoiceRows->sum('pendapatan');
                $totalPemasukanLain = (float) $pemasukanRows->sum('jumlah');
                $totalPengeluaranPembelanjaan = (float) $pengeluaranPembelanjaanRows->sum('total');
                $totalPengeluaranOperasional = (float) $pengeluaranRows->sum('total');
                $totalPengeluaran = $totalPengeluaranPembelanjaan + $totalPengeluaranOperasional;
                $activeSppg = $sppgId ? Sppg::query()->find($sppgId) : null;

                return [
                    'filters' => [
                        'tanggal_awal' => $tanggalAwalString,
                        'tanggal_akhir' => $tanggalAkhirString,
                        'sppg_id' => $sppgId,
                        'sppg' => $activeSppg?->nama_sppg,
                    ],
                    'summary' => [
                        'total_pendapatan_penjualan' => $totalPendapatanPenjualan,
                        'total_pemasukan_lain' => $totalPemasukanLain,
                        'total_pengeluaran_pembelanjaan' => $totalPengeluaranPembelanjaan,
                        'total_pengeluaran_operasional' => $totalPengeluaranOperasional,
                        'total_pengeluaran' => $totalPengeluaran,
                        'laba_bersih' => ($totalPendapatanPenjualan + $totalPemasukanLain) - ($totalPengeluaranPembelanjaan + $totalPengeluaranOperasional),
                    ],
                    'invoice_rows' => $invoiceRows->values()->all(),
                    'pemasukan_rows' => $pemasukanRows->values()->all(),
                    'pengeluaran_pembelanjaan_rows' => $pengeluaranPembelanjaanRows->values()->all(),
                    'pengeluaran_rows' => $pengeluaranRows->values()->all(),
                    'sppg_options' => Sppg::query()
                        ->orderBy('nama_sppg')
                        ->get(['id', 'nama_sppg'])
                        ->map(fn (Sppg $sppg): array => [
                            'id' => $sppg->id,
                            'nama_sppg' => $sppg->nama_sppg,
                        ])
                        ->values()
                        ->all(),
                ];
            }
        );

        return response()->json([
            'message' => 'Laporan laba rugi transaksional berhasil diambil.',
            'data' => $report,
        ]);
    }

    private function buildInvoiceRows(Carbon $tanggalAwal, Carbon $tanggalAkhir, ?int $sppgId): Collection
    {
        $invoicePenjualan = InvoicePenjualan::query()
            ->with([
                'penjualan:id,tanggal',
                'sppg:id,nama_sppg',
            ])
            ->whereHas('penjualan', function ($query) use ($tanggalAwal, $tanggalAkhir): void {
                $query->whereBetween('tanggal', [
                    $tanggalAwal->toDateString(),
                    $tanggalAkhir->toDateString(),
                ]);
            })
            ->when($sppgId, fn ($query) => $query->where('sppg_id', $sppgId))
            ->orderByDesc('tanggal_invoice')
            ->orderByDesc('id')
            ->get();

        $noPoLookup = TandaTerima::query()
            ->when($sppgId, fn ($query) => $query->where('sppg_id', $sppgId))
            ->whereBetween('tanggal', [
                $tanggalAwal->toDateString(),
                $tanggalAkhir->toDateString(),
            ])
            ->get(['tanggal', 'sppg_id', 'no_po'])
            ->mapWithKeys(function (TandaTerima $tandaTerima): array {
                return [
                    $tandaTerima->tanggal.'|'.$tandaTerima->sppg_id => $tandaTerima->no_po,
                ];
            });

        return $invoicePenjualan->map(function (InvoicePenjualan $invoice) use ($noPoLookup): array {
            $tanggalKirim = $invoice->penjualan?->tanggal?->format('Y-m-d');
            $key = $tanggalKirim && $invoice->sppg_id
                ? $tanggalKirim.'|'.$invoice->sppg_id
                : null;

            return [
                'id' => $invoice->id,
                'tanggal_kirim' => $tanggalKirim,
                'tanggal_invoice' => $invoice->tanggal_invoice?->format('Y-m-d'),
                'nomor_invoice' => $invoice->nomor_invoice,
                'no_po' => $key ? ($noPoLookup[$key] ?? '-') : '-',
                'sppg' => $invoice->sppg?->nama_sppg ?? '-',
                'pendapatan' => (float) $invoice->total_tagihan,
                'status_pembayaran' => $invoice->status_pembayaran,
            ];
        });
    }

    private function buildPemasukanRows(Carbon $tanggalAwal, Carbon $tanggalAkhir): Collection
    {
        return Pemasukan::query()
            ->whereBetween('tanggal', [
                $tanggalAwal->toDateString(),
                $tanggalAkhir->toDateString(),
            ])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Pemasukan $pemasukan): array => [
                'id' => $pemasukan->id,
                'tanggal' => $pemasukan->tanggal?->format('Y-m-d'),
                'jenis' => $pemasukan->jenis,
                'jumlah' => (float) $pemasukan->jumlah,
                'keterangan' => $pemasukan->keterangan,
            ]);
    }

    private function buildPengeluaranPembelanjaanRows(Carbon $tanggalAwal, Carbon $tanggalAkhir): Collection
    {
        return WarehouseInbound::query()
            ->whereBetween('tanggal_masuk', [
                $tanggalAwal->toDateString(),
                $tanggalAkhir->toDateString(),
            ])
            ->orderByDesc('tanggal_masuk')
            ->orderByDesc('id')
            ->get()
            ->map(fn (WarehouseInbound $inbound): array => [
                'id' => $inbound->id,
                'tanggal' => $inbound->tanggal_masuk?->format('Y-m-d'),
                'nama_barang' => $inbound->nama_barang,
                'kategori' => $inbound->kategori,
                'nama_supplier' => $inbound->nama_supplier,
                'qty' => (float) $inbound->qty,
                'satuan' => $inbound->satuan,
                'harga_satuan' => (float) $inbound->harga_satuan,
                'total' => (float) ($inbound->total_harga ?? ((float) $inbound->qty * (float) $inbound->harga_satuan)),
            ]);
    }

    private function buildPengeluaranRows(Carbon $tanggalAwal, Carbon $tanggalAkhir): Collection
    {
        return Pengeluaran::query()
            ->whereBetween('tanggal_keluar', [
                $tanggalAwal->toDateString(),
                $tanggalAkhir->toDateString(),
            ])
            ->orderByDesc('tanggal_keluar')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Pengeluaran $pengeluaran): array => [
                'id' => $pengeluaran->id,
                'tanggal' => $pengeluaran->tanggal_keluar?->format('Y-m-d'),
                'nama_operasional' => $pengeluaran->nama_operasional,
                'qty' => (float) $pengeluaran->qty,
                'satuan' => $pengeluaran->satuan,
                'harga_satuan' => (float) $pengeluaran->harga_satuan,
                'total' => (float) $pengeluaran->qty * (float) $pengeluaran->harga_satuan,
            ]);
    }
}
