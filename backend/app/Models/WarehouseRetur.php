<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseRetur extends Model
{
    use HasFactory;

    protected $table = 'warehouse_retur';

    protected $fillable = [
        'nama_barang',
        'qty_retur',
        'satuan_terkecil',
        'harga_beli',
        'alasan',
    ];

    protected $casts = [
        'qty_retur' => 'decimal:2',
        'harga_beli' => 'decimal:2',
    ];
}
