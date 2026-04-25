<?php

namespace App\Models\WarehouseSystem;

use App\Models\MasterData\Gudang;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseRetur extends Model
{
    use HasFactory;

    protected $table = 'warehouse_retur';

    protected $fillable = [
        'gudang_id',
        'jenis_stok',
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

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }
}
