<?php

namespace App\Models\MasterData;

use Database\Factories\SppgFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sppg extends Model
{
    /** @use HasFactory<SppgFactory> */
    use HasFactory;

    protected $table = 'sppg';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama_sppg',
        'alamat',
        'nama_yayasan',
        'nama_penanggungjawab',
        'no_penanggungjawab',
    ];

    protected static function newFactory(): SppgFactory
    {
        return SppgFactory::new();
    }
}
