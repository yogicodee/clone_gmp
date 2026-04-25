<?php

namespace Database\Factories;

use App\Models\MasterData\Gudang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gudang>
 */
class GudangFactory extends Factory
{
    protected $model = Gudang::class;

    /**
     * @return array{nama_gudang: string, alamat: string, nama_pic: string, no_pic: string}
     */
    public function definition(): array
    {
        return [
            'nama_gudang' => 'Gudang '.fake()->city(),
            'alamat' => fake()->address(),
            'nama_pic' => fake()->name(),
            'no_pic' => fake()->numerify('08##########'),
        ];
    }
}
