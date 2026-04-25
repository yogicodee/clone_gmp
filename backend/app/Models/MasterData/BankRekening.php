<?php

namespace App\Models\MasterData;

use Database\Factories\BankRekeningFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankRekening extends Model
{
    /** @use HasFactory<BankRekeningFactory> */
    use HasFactory;

    protected $table = 'bank_rekening';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama_bank',
        'no_rek',
        'atas_nama',
        'cabang',
    ];

    protected static function newFactory(): BankRekeningFactory
    {
        return BankRekeningFactory::new();
    }
}
