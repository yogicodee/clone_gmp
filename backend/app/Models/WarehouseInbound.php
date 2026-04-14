<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseInbound extends Model
{
    use HasFactory;

    protected $table = 'warehouse_inbounds';

    protected $fillable = [
        'nama_barang',
        'tanggal_masuk',
        'qty',
        'satuan',
        'harga_satuan',
        'total_harga',
        'nama_supplier',
    ];

    protected $casts = [
        'tanggal_masuk' => 'date',
        'qty' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'total_harga' => 'decimal:2',
    ];
}
