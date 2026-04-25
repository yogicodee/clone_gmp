<?php

namespace App\Models\MasterData;

use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    protected $table = 'supplier';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'alamat',
        'no_telp',
        'kategori',
    ];

    protected static function newFactory(): SupplierFactory
    {
        return SupplierFactory::new();
    }
}
