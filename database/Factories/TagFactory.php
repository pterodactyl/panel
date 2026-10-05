<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Pterodactyl\Models\Tag;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tag::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            // Slugs are stored verbatim, so the factory does not transform the name
            // beyond making it a single token that reads like a real tag.
            'slug' => str_replace(' ', '-', mb_strtolower($name)),
            'color' => fake()->hexColor(),
            'legacy_nest_id' => fake()->numberBetween(1, 1000),
        ];
    }

    /**
     * A built-in game tag, whose name and colour come from the EggSpecificTags enum
     * rather than the stored columns.
     */
    public function predefined(string $slug = 'minecraft'): self
    {
        return $this->state(fn (): array => [
            'name' => $slug,
            'slug' => $slug,
            'color' => null,
        ]);
    }
}
