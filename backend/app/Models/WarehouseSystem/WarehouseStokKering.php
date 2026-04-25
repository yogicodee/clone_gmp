<?php

namespace App\Models\WarehouseSystem;

use App\Models\MasterData\Gudang;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseStokKering extends Model
{
    use HasFactory;

    protected $table = 'warehouse_stok_kering';

    protected $fillable = [
        'warehouse_inbound_id',
        'gudang_id',
        'nama_barang',
        'qty',
        'satuan_terkecil',
        'harga_beli',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'harga_beli' => 'decimal:2',
    ];

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    public function inbound(): BelongsTo
    {
        return $this->belongsTo(WarehouseInbound::class, 'warehouse_inbound_id');
    }
}
