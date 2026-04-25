<?php

namespace Database\Factories;

use App\Models\MasterData\Perusahaan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Perusahaan>
 */
class PerusahaanFactory extends Factory
{
    protected $model = Perusahaan::class;

    /**
     * @return array{nama_perusahaan: string, alamat: string, nama_pic: string}
     */
    public function definition(): array
    {
        return [
            'nama_perusahaan' => fake()->company(),
            'alamat' => fake()->address(),
            'nama_pic' => fake()->name(),
        ];
    }
}
