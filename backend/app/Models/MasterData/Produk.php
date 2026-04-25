<?php

namespace App\Models\MasterData;

use Database\Factories\ProdukFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    /** @use HasFactory<ProdukFactory> */
    use HasFactory;

    protected $table = 'produk';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sku',
        'nama',
        'kategori',
        'satuan',
    ];

    protected static function newFactory(): ProdukFactory
    {
        return ProdukFactory::new();
    }
}
