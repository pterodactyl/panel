<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;

/**
 * @extends Factory<EggVariable>
 */
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
            'egg_id' => Egg::factory(),
            'name' => fake()->unique()->firstName(),
            'description' => fake()->sentence(),
            'env_variable' => Str::upper(Str::replaceArray(' ', ['_'], fake()->words(2, true))),
            'default_value' => fake()->colorName(),
            'user_viewable' => 0,
            'user_editable' => 0,
            'rules' => 'required|string',
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the egg variable is viewable.
     */
    public function viewable(): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_viewable' => 1,
        ]);
    }

    /**
     * Indicate that the egg variable is editable.
     */
    public function editable(): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_editable' => 1,
        ]);
    }
}
