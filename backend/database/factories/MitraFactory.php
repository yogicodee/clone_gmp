<?php

namespace Database\Factories;

use App\Models\MasterData\Mitra;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mitra>
 */
class MitraFactory extends Factory
{
    protected $model = Mitra::class;

    /**
     * @return array{nama_yayasan: string, alamat: string, nama_pic: string, no_pic: string}
     */
    public function definition(): array
    {
        return [
            'nama_yayasan' => 'Yayasan '.fake()->company(),
            'alamat' => fake()->address(),
            'nama_pic' => fake()->name(),
            'no_pic' => fake()->numerify('08##########'),
        ];
    }
}
