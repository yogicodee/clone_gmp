<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordActivityLog
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldLog($request, $response)) {
            return $response;
        }

        /** @var User|null $user */
        $user = $request->user();

        $path = preg_replace('#^api/#', '', $request->path()) ?? $request->path();
        $module = $this->resolveModule($path);
        $action = $this->resolveAction($request->method());
        $target = $this->resolveTarget($request);

        ActivityLog::query()->create([
            'user_id' => $user?->id,
            'user_name' => $this->resolveUserName($user),
            'module' => $module,
            'action' => $action,
            'method' => $request->method(),
            'request_path' => '/'.$path,
            'description' => trim(sprintf('%s %s%s', $action, $module, $target ? ': '.$target : '')),
            'metadata' => [
                'route_parameters' => $request->route()?->parameters() ?? [],
                'payload' => $request->except(['password', 'password_confirmation']),
            ],
            'ip_address' => $request->ip(),
        ]);

        return $response;
    }

    private function shouldLog(Request $request, Response $response): bool
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return false;
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            return false;
        }

        $path = preg_replace('#^api/#', '', $request->path()) ?? $request->path();

        return ! in_array($path, ['auth/logout', 'activity-logs'], true)
            && ! str_starts_with($path, 'activity-logs/');
    }

    private function resolveAction(string $method): string
    {
        return match ($method) {
            'POST' => 'Menambahkan',
            'PUT', 'PATCH' => 'Mengubah',
            'DELETE' => 'Menghapus',
            default => 'Melakukan aksi pada',
        };
    }

    private function resolveModule(string $path): string
    {
        $segments = explode('/', trim($path, '/'));
        $resource = $segments[0] ?? 'sistem';

        return match ($resource) {
            'wilayah' => 'Wilayah & Lokasi',
            'supplier' => 'Supplier',
            'mitra' => 'Mitra',
            'sppg' => 'SPPG',
            'produk' => 'Produk & Barang',
            'gudang' => 'Gudang',
            'armada' => 'Armada',
            'karyawan' => 'Karyawan',
            'bank-rekening' => 'Bank & Rekening',
            'kategori' => 'Kategori & Satuan',
            'perusahaan' => 'Perusahaan',
            'inbound' => 'Inbound',
            'stok-kering' => 'Cek Stok Kering',
            'stok-basah' => 'Cek Stok Basah',
            'retur-rusak' => 'Retur/Rusak',
            'order-penawaran' => 'Order Penawaran',
            'daftar-pembelanjaan' => 'Daftar Pembelanjaan',
            'daftar-pembelanjaan-supplier' => 'Daftar Pembelanjaan Supplier',
            'penjualan' => 'Penjualan',
            'surat-jalan' => 'Surat Jalan',
            'tanda-terima' => 'Tanda Terima',
            'invoice-penjualan' => 'Invoice Penjualan',
            'pemasukan' => 'Pemasukan',
            'pengeluaran' => 'Pengeluaran',
            'users' => 'Users',
            default => str($resource)->replace('-', ' ')->title()->toString(),
        };
    }

    private function resolveTarget(Request $request): string
    {
        $payload = $request->except(['password', 'password_confirmation']);

        foreach ([
            'nomor_invoice',
            'nomor_tanda_terima',
            'nomor_surat_jalan',
            'kode_penjualan',
            'nama_sppg',
            'nama_gudang',
            'nama_unit',
            'nama_barang',
            'nama_yayasan',
            'nama_bank',
            'nama',
            'sku',
            'no_rek',
            'no_po',
            'tanggal',
        ] as $key) {
            if (isset($payload[$key]) && trim((string) $payload[$key]) !== '') {
                return trim((string) $payload[$key]);
            }
        }

        $routeId = $request->route('id')
            ?? $request->route('user')
            ?? $request->route('supplier')
            ?? $request->route('mitra')
            ?? $request->route('sppg')
            ?? $request->route('produk')
            ?? $request->route('gudang')
            ?? $request->route('armada')
            ?? $request->route('karyawan')
            ?? $request->route('bankRekening')
            ?? $request->route('kategori')
            ?? $request->route('wilayah')
            ?? $request->route('perusahaan')
            ?? $request->route('penjualan')
            ?? $request->route('suratJalan')
            ?? $request->route('tandaTerima')
            ?? $request->route('orderPenawaran')
            ?? $request->route('daftarPembelanjaan')
            ?? $request->route('invoicePenjualan');

        if (is_object($routeId) && isset($routeId->id)) {
            return 'ID '.$routeId->id;
        }

        return $routeId ? 'ID '.$routeId : '';
    }

    private function resolveUserName(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        return $user->nama ?: $user->name ?: $user->email;
    }
}
