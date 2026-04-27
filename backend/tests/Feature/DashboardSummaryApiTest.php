<?php

namespace Tests\Feature;

use App\Models\TransaksiPenjualan\Penjualan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSummaryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_summary_returns_today_global_omset_from_completed_penjualan(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 27, 8, 0, 0, 'Asia/Jakarta'));

        Penjualan::query()->create([
            'kode_penjualan' => 'TRX-001',
            'tanggal' => '2026-04-27',
            'status' => 'selesai',
            'total_harga' => 1500000,
        ]);

        Penjualan::query()->create([
            'kode_penjualan' => 'TRX-002',
            'tanggal' => '2026-04-27',
            'status' => 'selesai',
            'total_harga' => 2750000,
        ]);

        Penjualan::query()->create([
            'kode_penjualan' => 'TRX-003',
            'tanggal' => '2026-04-27',
            'status' => 'draft',
            'total_harga' => 999999,
        ]);

        Penjualan::query()->create([
            'kode_penjualan' => 'TRX-004',
            'tanggal' => '2026-04-26',
            'status' => 'selesai',
            'total_harga' => 5000000,
        ]);

        $this->getJson('/api/dashboard/summary')
            ->assertOk()
            ->assertJsonPath('message', 'Ringkasan dashboard berhasil diambil.')
            ->assertJsonPath('data.tanggal', '2026-04-27')
            ->assertJsonPath('data.omset_hari_ini', 4250000);

        Carbon::setTestNow();
    }
}
