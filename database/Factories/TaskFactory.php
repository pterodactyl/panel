<?php

namespace Database\Factories;

use Pterodactyl\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Task::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'sequence_id' => $this->faker->randomNumber(1),
            'action' => 'command',
            'payload' => 'test command',
            'time_offset' => 120,
            'is_queued' => false,
        ];
    }
}
