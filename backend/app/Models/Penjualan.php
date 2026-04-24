<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Penjualan extends Model
{
    use HasFactory;

    protected $table = 'penjualan';

    protected $fillable = [
        'kode_penjualan',
        'tanggal',
        'status',
        'total_harga',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'total_harga' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PenjualanItem::class, 'penjualan_id');
    }
}
