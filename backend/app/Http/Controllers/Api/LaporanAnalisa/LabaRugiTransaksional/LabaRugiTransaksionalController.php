<?php

namespace App\Http\Controllers\Api\LaporanAnalisa\LabaRugiTransaksional;

use App\Http\Controllers\Controller;
use App\Models\TransaksiPenjualan\Penjualan;
use App\Models\WarehouseSystem\WarehouseInbound;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class LabaRugiTransaksionalController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'periode' => ['nullable', Rule::in(['harian', 'bulanan', 'tahunan'])],
            'tanggal' => ['nullable', 'date'],
        ]);

        $periode = $validated['periode'] ?? 'bulanan';
        $tanggal = isset($validated['tanggal'])
            ? Carbon::parse($validated['tanggal'], 'Asia/Jakarta')
            : Carbon::today('Asia/Jakarta');

        $penjualan = Penjualan::query()
            ->where('status', 'selesai')
            ->get(['tanggal', 'total_harga']);

        $inbounds = WarehouseInbound::query()
            ->get(['tanggal_masuk', 'total_harga']);

        $rows = $this->buildRows($penjualan, $inbounds, $periode, $tanggal);

        return response()->json([
            'message' => 'Laporan laba rugi transaksional berhasil diambil.',
            'data' => [
                'periode' => $periode,
                'tanggal_acuan' => $tanggal->toDateString(),
                'total_pendapatan' => (float) $rows->sum('pendapatan'),
                'total_beban' => (float) $rows->sum('beban'),
                'total_laba_rugi' => (float) $rows->sum('laba_rugi'),
                'rows' => $rows->values(),
            ],
        ]);
    }

    private function buildRows(Collection $penjualan, Collection $inbounds, string $periode, Carbon $tanggal): Collection
    {
        $filteredPenjualan = $penjualan
            ->filter(fn ($item): bool => $this->matchesPeriod(Carbon::parse($item->tanggal), $periode, $tanggal))
            ->groupBy(fn ($item): string => Carbon::parse($item->tanggal)->toDateString())
            ->map(fn (Collection $items): float => (float) $items->sum('total_harga'));

        $filteredInbounds = $inbounds
            ->filter(fn ($item): bool => $this->matchesPeriod(Carbon::parse($item->tanggal_masuk), $periode, $tanggal))
            ->groupBy(fn ($item): string => Carbon::parse($item->tanggal_masuk)->toDateString())
            ->map(fn (Collection $items): float => (float) $items->sum('total_harga'));

        $dates = $filteredPenjualan->keys()
            ->merge($filteredInbounds->keys())
            ->unique()
            ->sort()
            ->values();

        return $dates->map(function (string $date) use ($filteredPenjualan, $filteredInbounds): array {
            $pendapatan = (float) ($filteredPenjualan[$date] ?? 0);
            $beban = (float) ($filteredInbounds[$date] ?? 0);

            return [
                'tanggal' => $date,
                'pendapatan' => $pendapatan,
                'beban' => $beban,
                'laba_rugi' => $pendapatan - $beban,
            ];
        })->sortByDesc('tanggal')->values();
    }

    private function matchesPeriod(Carbon $sourceDate, string $periode, Carbon $tanggal): bool
    {
        return match ($periode) {
            'harian' => $sourceDate->isSameDay($tanggal),
            'tahunan' => $sourceDate->year === $tanggal->year,
            default => $sourceDate->year === $tanggal->year
                && $sourceDate->month === $tanggal->month,
        };
    }
}
