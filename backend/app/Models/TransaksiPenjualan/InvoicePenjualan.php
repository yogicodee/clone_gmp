<?php

namespace App\Models\TransaksiPenjualan;

use App\Models\MasterData\Sppg;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePenjualan extends Model
{
    use HasFactory;

    protected $table = 'invoice_penjualan';

    protected $fillable = [
        'nomor_invoice',
        'penjualan_id',
        'sppg_id',
        'tanggal_invoice',
        'total_tagihan',
        'status_pembayaran',
    ];

    protected $casts = [
        'tanggal_invoice' => 'date:Y-m-d',
        'total_tagihan' => 'decimal:2',
    ];

    protected $appends = [
        'kode_penjualan',
        'nama_sppg',
    ];

    public function penjualan(): BelongsTo
    {
        return $this->belongsTo(Penjualan::class, 'penjualan_id');
    }

    public function sppg(): BelongsTo
    {
        return $this->belongsTo(Sppg::class, 'sppg_id');
    }

    public function getKodePenjualanAttribute(): ?string
    {
        return $this->penjualan?->kode_penjualan;
    }

    public function getNamaSppgAttribute(): ?string
    {
        return $this->sppg?->nama_sppg;
    }
}
