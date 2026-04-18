<?php

namespace App\Models;

use Database\Factories\PerusahaanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Perusahaan extends Model
{
    /** @use HasFactory<PerusahaanFactory> */
    use HasFactory;

    protected $table = 'perusahaan';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama_perusahaan',
        'alamat',
        'nama_pic',
    ];
}
