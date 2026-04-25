<?php

namespace App\Models\MasterData;

use Database\Factories\GudangFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gudang extends Model
{
    /** @use HasFactory<GudangFactory> */
    use HasFactory;

    protected $table = 'gudang';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama_gudang',
        'alamat',
        'nama_pic',
        'no_pic',
    ];

    protected static function newFactory(): GudangFactory
    {
        return GudangFactory::new();
    }
}
