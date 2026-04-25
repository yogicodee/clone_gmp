<?php

namespace Tests\Feature;

use App\Models\MasterData\BankRekening;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankRekeningApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_bank_rekening_index_returns_paginated_records(): void
    {
        BankRekening::factory()->create([
            'nama_bank' => 'BCA',
            'no_rek' => '1234567890',
            'atas_nama' => 'PT Maju Jaya',
            'cabang' => 'Bandung',
        ]);

        BankRekening::factory()->create([
            'nama_bank' => 'BRI',
            'no_rek' => '9876543210',
            'atas_nama' => 'CV Aulia',
            'cabang' => 'Jakarta',
        ]);

        BankRekening::factory()->create([
            'nama_bank' => 'BNI',
            'no_rek' => '1112223334',
            'atas_nama' => 'PT Nusantara',
            'cabang' => 'Surabaya',
        ]);

        $response = $this->getJson('/api/bank-rekening?search=PT&sort_field=atas_nama&sort_order=asc&per_page=10');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Data bank dan rekening berhasil diambil.')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.atas_nama', 'PT Maju Jaya')
            ->assertJsonPath('data.1.atas_nama', 'PT Nusantara');
    }

    public function test_bank_rekening_can_be_created(): void
    {
        $response = $this->postJson('/api/bank-rekening', [
            'nama_bank' => 'Mandiri',
            'no_rek' => '555666777888',
            'atas_nama' => 'PT Sejahtera',
            'cabang' => 'Bogor',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Bank dan rekening berhasil ditambahkan.')
            ->assertJsonPath('data.nama_bank', 'Mandiri')
            ->assertJsonPath('data.no_rek', '555666777888');

        $this->assertDatabaseHas('bank_rekening', [
            'nama_bank' => 'Mandiri',
            'no_rek' => '555666777888',
            'atas_nama' => 'PT Sejahtera',
            'cabang' => 'Bogor',
        ]);
    }

    public function test_bank_rekening_rejects_invalid_account_number(): void
    {
        $response = $this->postJson('/api/bank-rekening', [
            'nama_bank' => 'BCA',
            'no_rek' => '12345AB',
            'atas_nama' => 'PT Salah',
            'cabang' => 'Bandung',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('errors.no_rek.0', 'No rekening hanya boleh berisi angka.');
    }

    public function test_bank_rekening_detail_can_be_viewed(): void
    {
        $rekening = BankRekening::factory()->create([
            'nama_bank' => 'BCA',
            'no_rek' => '1234567890',
        ]);

        $response = $this->getJson('/api/bank-rekening/'.$rekening->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $rekening->id)
            ->assertJsonPath('data.no_rek', '1234567890');
    }

    public function test_bank_rekening_can_be_updated(): void
    {
        $rekening = BankRekening::factory()->create([
            'nama_bank' => 'BRI',
            'no_rek' => '9876543210',
        ]);

        $response = $this->putJson('/api/bank-rekening/'.$rekening->id, [
            'nama_bank' => 'BRI Syariah',
            'no_rek' => '999000111222',
            'atas_nama' => 'CV Baru',
            'cabang' => 'Depok',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Bank dan rekening berhasil diperbarui.')
            ->assertJsonPath('data.nama_bank', 'BRI Syariah');

        $this->assertDatabaseHas('bank_rekening', [
            'id' => $rekening->id,
            'nama_bank' => 'BRI Syariah',
            'no_rek' => '999000111222',
        ]);
    }

    public function test_bank_rekening_can_be_deleted(): void
    {
        $rekening = BankRekening::factory()->create();

        $response = $this->deleteJson('/api/bank-rekening/'.$rekening->id);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Bank dan rekening berhasil dihapus.');

        $this->assertDatabaseMissing('bank_rekening', [
            'id' => $rekening->id,
        ]);
    }
}
