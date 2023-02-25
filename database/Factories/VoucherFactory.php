<?php

namespace Database\Factories;

use App\Models\voucher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VoucherFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = voucher::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'memo' => $this->faker->word(),
            'code' => Str::random(36),
            'credits' => $this->faker->numberBetween(100, 1000),
            'uses' => $this->faker->numberBetween(1, 1000),
            'expires_at' => now()->addDays($this->faker->numberBetween(1, 90))->format('d-m-Y'),
        ];
    }
}
