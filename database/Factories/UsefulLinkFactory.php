<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UsefulLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'icon' => 'fas fa-user',
            'title' => $this->faker->text(30),
            'link' => $this->faker->url(),
            'description' => $this->faker->text(),
        ];
    }
}
