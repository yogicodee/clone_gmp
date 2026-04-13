<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DaftarPembelanjaanItem extends Model
{
    use HasFactory;

    protected $table = 'daftar_pembelanjaan_items';

    protected $fillable = [
        'daftar_pembelanjaan_id',
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
}
