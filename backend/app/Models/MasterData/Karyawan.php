<?php

namespace App\Models\MasterData;

use Database\Factories\KaryawanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    /** @use HasFactory<KaryawanFactory> */
    use HasFactory;

    protected $table = 'karyawan';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'alamat',
        'no_hp',
        'jabatan',
        'tanggal_masuk',
        'status',
    ];

    protected static function newFactory(): KaryawanFactory
    {
        return KaryawanFactory::new();
    }
}
