<?php

namespace App\Models\TransaksiPenjualan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TandaTerimaItem extends Model
{
    use HasFactory;

    protected $table = 'tanda_terima_items';

    protected $fillable = [
        'tanda_terima_id',
        'penjualan_item_id',
        'nama_barang',
        'qty',
        'satuan',
        'keterangan',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
    ];

    public function tandaTerima(): BelongsTo
    {
        return $this->belongsTo(TandaTerima::class, 'tanda_terima_id');
    }

    public function penjualanItem(): BelongsTo
    {
        return $this->belongsTo(PenjualanItem::class, 'penjualan_item_id');
    }
}
