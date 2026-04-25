<?php

namespace Database\Factories;

use App\Models\MasterData\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kategori>
 */
class KategoriFactory extends Factory
{
    protected $model = Kategori::class;

    /**
     * @return array{kode: string, nama_satuan: string}
     */
    public function definition(): array
    {
        $pairs = [
            ['kode' => 'PCS', 'nama_satuan' => 'Pieces'],
            ['kode' => 'BOX', 'nama_satuan' => 'Box'],
            ['kode' => 'KG', 'nama_satuan' => 'Kilogram'],
            ['kode' => 'LTR', 'nama_satuan' => 'Liter'],
        ];

        return fake()->randomElement($pairs);
    }
}
