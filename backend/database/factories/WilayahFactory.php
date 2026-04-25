<?php

namespace Database\Factories;

use App\Models\MasterData\Wilayah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wilayah>
 */
class WilayahFactory extends Factory
{
    protected $model = Wilayah::class;

    /**
     * @return array{nama: string, alamat: string}
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->city(),
            'alamat' => fake()->address(),
        ];
    }
}
