<?php

namespace App\Models\MasterData;

use Database\Factories\WilayahFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wilayah extends Model
{
    /** @use HasFactory<WilayahFactory> */
    use HasFactory;

    protected $table = 'wilayah';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'alamat',
    ];

    protected static function newFactory(): WilayahFactory
    {
        return WilayahFactory::new();
    }
}
