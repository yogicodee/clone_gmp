<?php

namespace App\Http\Controllers\Api\Dashboard\DashboardCashflowTrend;

use App\Http\Controllers\Controller;
use App\Models\KeuanganAkuntansi\Pemasukan;
use App\Models\KeuanganAkuntansi\Pengeluaran;
use App\Models\TransaksiPenjualan\InvoicePenjualan;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardCashflowTrendController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tanggal_awal' => ['nullable', 'date'],
            'tanggal_akhir' => ['nullable', 'date', 'after_or_equal:tanggal_awal'],
        ]);

        $today = Carbon::today('Asia/Jakarta');
        $tanggalAwal = isset($validated['tanggal_awal'])
            ? Carbon::parse($validated['tanggal_awal'], 'Asia/Jakarta')->startOfDay()
            : $today->copy()->startOfMonth()->startOfDay();
        $tanggalAkhir = isset($validated['tanggal_akhir'])
            ? Carbon::parse($validated['tanggal_akhir'], 'Asia/Jakarta')->startOfDay()
            : $today->copy()->startOfDay();

        $isMonthly = $tanggalAwal->diffInDays($tanggalAkhir) > 31;
        $points = $isMonthly
            ? $this->buildMonthlyRangeTrend($tanggalAwal, $tanggalAkhir)
            : $this->buildDailyRangeTrend($tanggalAwal, $tanggalAkhir);

        return response()->json([
            'message' => 'Data tren dashboard berhasil diambil.',
            'data' => [
                'tanggal_awal' => $tanggalAwal->toDateString(),
                'tanggal_akhir' => $tanggalAkhir->toDateString(),
                'granularitas' => $isMonthly ? 'bulanan' : 'harian',
                'points' => $points->values(),
            ],
        ]);
    }

    private function buildDailyRangeTrend(Carbon $tanggalAwal, Carbon $tanggalAkhir): Collection
    {
        $days = $tanggalAwal->diffInDays($tanggalAkhir);

        return collect(range(0, $days))->map(function (int $offset) use ($tanggalAwal): array {
            $date = $tanggalAwal->copy()->addDays($offset);
            $label = $date->locale('id')->translatedFormat('d M');

            return $this->buildPoint(
                $label,
                fn ($query, string $column) => $query->whereDate($column, $date->toDateString())
            );
        });
    }

    private function buildMonthlyRangeTrend(Carbon $tanggalAwal, Carbon $tanggalAkhir): Collection
    {
        $start = $tanggalAwal->copy()->startOfMonth();
        $end = $tanggalAkhir->copy()->startOfMonth();
        $months = $start->diffInMonths($end);

        return collect(range(0, $months))->map(function (int $offset) use ($start): array {
            $date = $start->copy()->addMonths($offset);
            $label = $date->locale('id')->translatedFormat('M y');

            return $this->buildPoint(
                $label,
                fn ($query, string $column) => $query
                    ->whereYear($column, $date->year)
                    ->whereMonth($column, $date->month)
            );
        });
    }

    private function buildPoint(string $label, callable $scope): array
    {
        $pendapatan = (float) $scope(
            InvoicePenjualan::query()->where('status_pembayaran', 'lunas'),
            'tanggal_invoice'
        )->sum('total_tagihan');

        $pemasukanLain = (float) $scope(Pemasukan::query(), 'tanggal')->sum('jumlah');

        $pengeluaran = (float) $scope(Pengeluaran::query(), 'tanggal_keluar')
            ->selectRaw('COALESCE(SUM(qty * harga_satuan), 0) as total_beban')
            ->value('total_beban');

        return [
            'label' => $label,
            'pendapatan' => $pendapatan,
            'pemasukan_lain' => $pemasukanLain,
            'pengeluaran' => $pengeluaran,
            'laba_bersih' => $pendapatan + $pemasukanLain - $pengeluaran,
        ];
    }
}
