<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class CacheInvalidation
{
    public const TAG_DASHBOARD_SUMMARY = 'dashboard-summary';
    public const TAG_DASHBOARD_SALES_BY_SPPG = 'dashboard-sales-by-sppg';
    public const TAG_LAPORAN_STOK_BARANG = 'laporan-stok-barang';
    public const TAG_LABA_RUGI_TRANSAKSIONAL = 'laba-rugi-transaksional';

    public static function flushDashboardSummary(): void
    {
        Cache::tags([self::TAG_DASHBOARD_SUMMARY])->flush();
    }

    public static function flushDashboardSalesBySppg(): void
    {
        Cache::tags([self::TAG_DASHBOARD_SALES_BY_SPPG])->flush();
    }

    public static function flushLaporanStokBarang(): void
    {
        Cache::tags([self::TAG_LAPORAN_STOK_BARANG])->flush();
    }

    public static function flushLabaRugiTransaksional(): void
    {
        Cache::tags([self::TAG_LABA_RUGI_TRANSAKSIONAL])->flush();
    }

    public static function flushFinancialCaches(): void
    {
        self::flushDashboardSummary();
        self::flushDashboardSalesBySppg();
        self::flushLabaRugiTransaksional();
    }

    public static function flushStockCaches(): void
    {
        self::flushDashboardSummary();
        self::flushLaporanStokBarang();
    }
}
