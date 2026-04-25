<?php

namespace App\Models\TransaksiPembelian;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderPenawaran extends Model
{
    use HasFactory;

    protected $table = 'order_penawaran';

    protected $fillable = [
        'tanggal_pesan',
        'tanggal_dikirim',
        'nama_pembeli',
        'keterangan',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderPenawaranItem::class, 'order_penawaran_id');
    }
}
