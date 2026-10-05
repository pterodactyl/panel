<?php

declare(strict_types=1);

namespace Database\Factories;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<Backup>
 */
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
            'server_id' => Server::factory()->withRelationships(),
            'uuid' => Uuid::uuid4()->toString(),
            'name' => fake()->sentence(),
            'ignored_files' => [],
            'disk' => Backup::ADAPTER_WINGS,
            'is_successful' => true,
            'created_at' => CarbonImmutable::now(),
            'completed_at' => CarbonImmutable::now(),
        ];
    }
}
