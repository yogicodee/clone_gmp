<?php

namespace Database\Factories;

use App\Models\MasterData\Armada;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Armada>
 */
class ArmadaFactory extends Factory
{
    protected $model = Armada::class;

    /**
     * @return array{nama_unit: string, no_pol: string, jenis_kendaraan: string}
     */
    public function definition(): array
    {
        return [
            'nama_unit' => fake()->randomElement(['Motor Operasional', 'Pickup Logistik', 'Mobil Box']),
            'no_pol' => strtoupper(fake()->bothify('? #### ??')),
            'jenis_kendaraan' => fake()->randomElement(['Roda 2', 'Roda 4']),
        ];
    }
}
