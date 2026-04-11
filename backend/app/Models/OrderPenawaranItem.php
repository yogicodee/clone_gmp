<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPenawaranItem extends Model
{
    use HasFactory;

    protected $table = 'order_penawaran_items';

    protected $fillable = [
        'order_penawaran_id',
        'nama_barang',
        'qty',
        'satuan',
        'harga_satuan',
        'keterangan',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
    ];

    public function orderPenawaran(): BelongsTo
    {
        return $this->belongsTo(OrderPenawaran::class, 'order_penawaran_id');
    }
}
