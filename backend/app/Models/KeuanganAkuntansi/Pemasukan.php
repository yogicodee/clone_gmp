<?php

namespace App\Models\KeuanganAkuntansi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pemasukan extends Model
{
    use HasFactory;

    protected $table = 'pemasukan';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tanggal',
        'jenis',
        'jumlah',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'jumlah' => 'decimal:2',
    ];
}
