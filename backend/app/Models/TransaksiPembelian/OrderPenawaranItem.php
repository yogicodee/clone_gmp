<?php

namespace App\Models\TransaksiPembelian;

use App\Models\MasterData\Kategori;
use App\Models\MasterData\Produk;
use App\Models\MasterData\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPenawaranItem extends Model
{
    use HasFactory;

    protected $table = 'order_penawaran_items';

    protected $fillable = [
        'order_penawaran_id',
        'produk_id',
        'kategori_id',
        'supplier_id',
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

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
