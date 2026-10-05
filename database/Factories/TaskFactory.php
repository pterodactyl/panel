<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Pterodactyl\Models\Schedule;

class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'schedule_id' => Schedule::factory(),
            'sequence_id' => fake()->numberBetween(1, 10),
            'action' => 'command',
            'payload' => 'test command',
            'time_offset' => 120,
            'is_queued' => false,
        ];
    }
}
