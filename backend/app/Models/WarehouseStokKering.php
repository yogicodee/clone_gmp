<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseStokKering extends Model
{
    use HasFactory;

    protected $table = 'warehouse_stok_kering';

    protected $fillable = [
        'nama_barang',
        'qty',
        'satuan_terkecil',
        'harga_beli',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'harga_beli' => 'decimal:2',
    ];
}
