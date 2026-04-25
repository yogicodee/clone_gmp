<?php

namespace App\Models\TransaksiPembelian;

use App\Models\MasterData\Kategori;
use App\Models\MasterData\Produk;
use App\Models\MasterData\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DaftarPembelanjaanItem extends Model
{
    use HasFactory;

    protected $table = 'daftar_pembelanjaan_items';

    protected $fillable = [
        'daftar_pembelanjaan_id',
        'supplier_id',
        'produk_id',
        'kategori_id',
        'nama_barang',
        'qty',
        'satuan',
        'stok',
        'kebutuhan',
        'nama_supplier',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'stok' => 'decimal:2',
        'kebutuhan' => 'decimal:2',
    ];

    public function daftarPembelanjaan(): BelongsTo
    {
        return $this->belongsTo(DaftarPembelanjaan::class, 'daftar_pembelanjaan_id');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
