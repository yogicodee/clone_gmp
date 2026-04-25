<?php

namespace Database\Factories;

use App\Models\MasterData\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * @return array{nama: string, alamat: string, no_telp: string, kategori: string}
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->company(),
            'alamat' => fake()->address(),
            'no_telp' => fake()->numerify('08##########'),
            'kategori' => fake()->randomElement([
                'Retail',
                'Distributor',
                'Grosir',
                'Supplier',
            ]),
        ];
    }
}
