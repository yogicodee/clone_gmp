<?php

namespace App\Models\MasterData;

use Database\Factories\KategoriFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    /** @use HasFactory<KategoriFactory> */
    use HasFactory;

    protected $table = 'kategori';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'kode',
        'nama_satuan',
    ];

    protected static function newFactory(): KategoriFactory
    {
        return KategoriFactory::new();
    }
}
