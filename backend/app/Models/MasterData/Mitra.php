<?php

namespace App\Models\MasterData;

use Database\Factories\MitraFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mitra extends Model
{
    /** @use HasFactory<MitraFactory> */
    use HasFactory;

    protected $table = 'mitra';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama_yayasan',
        'alamat',
        'nama_pic',
        'no_pic',
    ];

    protected static function newFactory(): MitraFactory
    {
        return MitraFactory::new();
    }
}
