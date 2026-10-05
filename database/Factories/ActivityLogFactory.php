<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Models\User;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ActivityLog::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'event' => 'user:account.email-changed',
            'ip' => fake()->ipv4(),
            'description' => null,
            'properties' => [
                'old' => 'old-user@example.com',
                'new' => 'new-user@example.com',
            ],
            'timestamp' => Date::now(),
        ];
    }

    /**
     * Assign a user actor to the activity log entry.
     */
    public function withActor(): self
    {
        return $this->state([
            'actor_id' => User::factory(),
            'actor_type' => 'user',
        ]);
    }
}
