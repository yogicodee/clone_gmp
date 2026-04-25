<?php

namespace Database\Factories;

use App\Models\MasterData\BankRekening;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankRekening>
 */
class BankRekeningFactory extends Factory
{
    protected $model = BankRekening::class;

    /**
     * @return array{nama_bank: string, no_rek: string, atas_nama: string, cabang: string}
     */
    public function definition(): array
    {
        return [
            'nama_bank' => fake()->randomElement(['BCA', 'BRI', 'BNI', 'Mandiri']),
            'no_rek' => fake()->numerify('##########'),
            'atas_nama' => fake()->company(),
            'cabang' => fake()->city(),
        ];
    }
}
