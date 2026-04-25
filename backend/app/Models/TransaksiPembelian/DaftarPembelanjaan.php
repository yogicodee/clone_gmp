<?php

namespace App\Models\TransaksiPembelian;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DaftarPembelanjaan extends Model
{
    use HasFactory;

    protected $table = 'daftar_pembelanjaan';

    protected $fillable = [
        'tanggal_pesan',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(DaftarPembelanjaanItem::class, 'daftar_pembelanjaan_id');
    }
}
