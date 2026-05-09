<?php

namespace App\Models\KeuanganAkuntansi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengeluaran extends Model
{
    use HasFactory;

    protected $table = 'pengeluaran';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama_operasional',
        'tanggal_keluar',
        'qty',
        'satuan',
        'harga_satuan',
    ];

    protected $casts = [
        'tanggal_keluar' => 'date:Y-m-d',
        'qty' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
    ];
}
