<?php

namespace Database\Factories;

use App\Models\MasterData\Karyawan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Karyawan>
 */
class KaryawanFactory extends Factory
{
    protected $model = Karyawan::class;

    /**
     * @return array{
     *     nama: string,
     *     alamat: string,
     *     no_hp: string,
     *     jabatan: string,
     *     tanggal_masuk: string,
     *     status: string
     * }
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'alamat' => fake()->address(),
            'no_hp' => fake()->numerify('08##########'),
            'jabatan' => fake()->randomElement(['Admin', 'Gudang', 'Logistik']),
            'tanggal_masuk' => fake()->date(),
            'status' => fake()->randomElement(['aktif', 'nonaktif']),
        ];
    }
}
