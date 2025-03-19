<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Tender;

class ChatFactory extends Factory
{
    public function definition(): array
    {
        return [
            'Tender_ID' => Tender::factory(), // Erstellt eine Tender-Referenz
            'User_ID' => User::factory(), // Erstellt eine User-Referenz
            'Timestamp' => $this->faker->dateTime(),
            'Content' => $this->faker->sentence(),
        ];
    }
}
