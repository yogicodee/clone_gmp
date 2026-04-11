<?php

namespace App\Models;

use Database\Factories\ArmadaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Armada extends Model
{
    /** @use HasFactory<ArmadaFactory> */
    use HasFactory;

    protected $table = 'armada';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama_unit',
        'no_pol',
        'jenis_kendaraan',
    ];
}
