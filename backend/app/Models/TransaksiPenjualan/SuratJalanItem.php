<?php

namespace App\Models\TransaksiPenjualan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratJalanItem extends Model
{
    use HasFactory;

    protected $table = 'surat_jalan_items';

    protected $fillable = [
        'surat_jalan_id',
        'penjualan_item_id',
        'nama_barang',
        'qty',
        'satuan',
        'keterangan',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
    ];

    public function suratJalan(): BelongsTo
    {
        return $this->belongsTo(SuratJalan::class, 'surat_jalan_id');
    }

    public function penjualanItem(): BelongsTo
    {
        return $this->belongsTo(PenjualanItem::class, 'penjualan_item_id');
    }
}
