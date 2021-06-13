<?php

namespace Database\Factories;

use Ramsey\Uuid\Uuid;
use Carbon\CarbonImmutable;
use Pterodactyl\Models\Backup;
use Illuminate\Database\Eloquent\Factories\Factory;

class BackupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Backup::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'uuid' => Uuid::uuid4()->toString(),
            'name' => $this->faker->sentence,
            'disk' => Backup::ADAPTER_WINGS,
            'is_successful' => true,
            'created_at' => CarbonImmutable::now(),
            'completed_at' => CarbonImmutable::now(),
        ];
    }
}
