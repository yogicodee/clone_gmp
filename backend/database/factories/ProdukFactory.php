<?php

namespace Database\Factories;

use App\Models\MasterData\Produk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Produk>
 */
class ProdukFactory extends Factory
{
    protected $model = Produk::class;

    /**
     * @return array{sku: string, nama: string, kategori: string, satuan: string}
     */
    public function definition(): array
    {
        return [
            'sku' => strtoupper(fake()->bothify('BRG-###')),
            'nama' => fake()->words(2, true),
            'kategori' => fake()->randomElement(['Kering', 'Basah']),
            'satuan' => fake()->randomElement(['PCS', 'BOX', 'KG', 'LITER']),
        ];
    }
}
