<?php

namespace Database\Factories;

use Pterodactyl\Models\ServerTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServerTransferFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ServerTransfer::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'old_additional_allocations' => [],
            'new_additional_allocations' => [],
            'successful' => null,
            'archived' => false,
        ];
    }
}
