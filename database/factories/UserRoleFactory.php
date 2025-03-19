<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class UserRoleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'User_ID' => User::factory(), // User-Referenz
            'Role' => $this->faker->randomElement(['User', 'Designer', 'Provider']),
        ];
    }
}
