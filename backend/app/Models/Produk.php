<?php

namespace App\Models;

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
}
