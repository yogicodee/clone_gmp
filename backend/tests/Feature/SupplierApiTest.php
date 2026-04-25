<?php

namespace Tests\Feature;

use App\Models\MasterData\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_index_returns_paginated_records(): void
    {
        Supplier::factory()->create([
            'nama' => 'Asikin Aurelia',
            'alamat' => 'Ploso',
            'no_telp' => '08123456789',
            'kategori' => 'Retail',
        ]);

        Supplier::factory()->create([
            'nama' => 'CV Aulia',
            'alamat' => 'Bandung',
            'no_telp' => '08345678901',
            'kategori' => 'Grosir',
        ]);

        Supplier::factory()->create([
            'nama' => 'PT Santika',
            'alamat' => 'Jakarta',
            'no_telp' => '08234567890',
            'kategori' => 'Distributor',
        ]);

        $response = $this->getJson('/api/supplier?search=08&sort_field=no_telp&sort_order=asc&per_page=10');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Data supplier berhasil diambil.')
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.no_telp', '08123456789')
            ->assertJsonPath('data.1.no_telp', '08234567890')
            ->assertJsonPath('data.2.no_telp', '08345678901');
    }

    public function test_supplier_can_be_created(): void
    {
        $response = $this->postJson('/api/supplier', [
            'nama' => 'PT Maju Jaya',
            'alamat' => 'Surabaya',
            'no_telp' => '08456789012',
            'kategori' => 'Supplier',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Supplier berhasil ditambahkan.')
            ->assertJsonPath('data.nama', 'PT Maju Jaya')
            ->assertJsonPath('data.no_telp', '08456789012')
            ->assertJsonPath('data.kategori', 'Supplier');

        $this->assertDatabaseHas('supplier', [
            'nama' => 'PT Maju Jaya',
            'alamat' => 'Surabaya',
            'no_telp' => '08456789012',
            'kategori' => 'Supplier',
        ]);
    }

    public function test_supplier_phone_can_use_plus_dash_spaces_and_parentheses(): void
    {
        $response = $this->postJson('/api/supplier', [
            'nama' => 'PT Format Nomor',
            'alamat' => 'Jakarta',
            'no_telp' => '+62 (812) 3456-789',
            'kategori' => 'Distributor',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.no_telp', '+62 (812) 3456-789');

        $this->assertDatabaseHas('supplier', [
            'nama' => 'PT Format Nomor',
            'no_telp' => '+62 (812) 3456-789',
        ]);
    }

    public function test_supplier_phone_rejects_invalid_characters(): void
    {
        $response = $this->postJson('/api/supplier', [
            'nama' => 'PT Invalid Nomor',
            'alamat' => 'Bandung',
            'no_telp' => '0812abc789',
            'kategori' => 'Retail',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.no_telp.0',
                'No telepon hanya boleh berisi angka dan karakter khusus tertentu.'
            );
    }

    public function test_supplier_detail_can_be_viewed(): void
    {
        $supplier = Supplier::factory()->create([
            'nama' => 'PT Santika',
            'alamat' => 'Jakarta',
            'no_telp' => '08234567890',
            'kategori' => 'Distributor',
        ]);

        $response = $this->getJson('/api/supplier/'.$supplier->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $supplier->id)
            ->assertJsonPath('data.nama', 'PT Santika')
            ->assertJsonPath('data.kategori', 'Distributor');
    }

    public function test_supplier_can_be_updated(): void
    {
        $supplier = Supplier::factory()->create([
            'nama' => 'CV Aulia',
            'alamat' => 'Bandung',
            'no_telp' => '08345678901',
            'kategori' => 'Grosir',
        ]);

        $response = $this->putJson('/api/supplier/'.$supplier->id, [
            'nama' => 'CV Aulia Baru',
            'alamat' => 'Bandung Barat',
            'no_telp' => '08345678999',
            'kategori' => 'Retail',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Supplier berhasil diperbarui.')
            ->assertJsonPath('data.nama', 'CV Aulia Baru')
            ->assertJsonPath('data.no_telp', '08345678999');

        $this->assertDatabaseHas('supplier', [
            'id' => $supplier->id,
            'nama' => 'CV Aulia Baru',
            'alamat' => 'Bandung Barat',
            'no_telp' => '08345678999',
            'kategori' => 'Retail',
        ]);
    }

    public function test_supplier_can_be_deleted(): void
    {
        $supplier = Supplier::factory()->create();

        $response = $this->deleteJson('/api/supplier/'.$supplier->id);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Supplier berhasil dihapus.');

        $this->assertDatabaseMissing('supplier', [
            'id' => $supplier->id,
        ]);
    }
}
