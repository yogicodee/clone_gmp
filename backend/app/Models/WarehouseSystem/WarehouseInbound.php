<?php

namespace App\Models\WarehouseSystem;

use App\Models\MasterData\Gudang;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseInbound extends Model
{
    use HasFactory;

    protected $table = 'warehouse_inbounds';

    protected $fillable = [
        'gudang_id',
        'nama_barang',
        'kategori',
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

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }
}
