<?php

namespace Database\Factories;

use App\Models\MasterData\Sppg;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sppg>
 */
class SppgFactory extends Factory
{
    protected $model = Sppg::class;

    /**
     * @return array{
     *     nama_sppg: string,
     *     alamat: string,
     *     nama_yayasan: string,
     *     nama_penanggungjawab: string,
     *     no_penanggungjawab: string
     * }
     */
    public function definition(): array
    {
        return [
            'nama_sppg' => 'SPPG '.fake()->city(),
            'alamat' => fake()->address(),
            'nama_yayasan' => 'Yayasan '.fake()->company(),
            'nama_penanggungjawab' => fake()->name(),
            'no_penanggungjawab' => fake()->numerify('08##########'),
        ];
    }
}
