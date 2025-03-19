<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Chat;
use App\Models\User;

class ChatMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'Chat_ID' => Chat::factory(), // Erstellt eine Chat-Referenz
            'User_ID' => User::factory(), // Erstellt eine User-Referenz
            'Timestamp' => $this->faker->dateTime(),
            'Content' => $this->faker->sentence(),
        ];
    }
}
