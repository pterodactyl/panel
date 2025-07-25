<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Pterodactyl\Models\EggVariable;
use Illuminate\Database\Eloquent\Factories\Factory;

class EggVariableFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EggVariable::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->firstName,
            'description' => $this->faker->sentence(),
            'env_variable' => Str::upper(Str::replaceArray(' ', ['_'], $this->faker->words(2, true))),
            'default_value' => $this->faker->colorName,
            'user_viewable' => 0,
            'user_editable' => 0,
            'rules' => 'required|string',
        ];
    }

    /**
     * Indicate that the egg variable is viewable.
     */
    public function viewable(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'user_viewable' => 1,
            ];
        });
    }

    /**
     * Indicate that the egg variable is editable.
     */
    public function editable(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'user_editable' => 1,
            ];
        });
    }
}
