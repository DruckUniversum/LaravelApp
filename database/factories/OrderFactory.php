<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Design;
use App\Models\User;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'User_ID' => User::factory(), // Erstellt eine User-Referenz
            'Design_ID' => Design::factory(), // Erstellt eine Design-Referenz
            'Paid_Price' => $this->faker->randomFloat(2, 20, 2000), // Zufälliger Preis
            'Payment_Status' => $this->faker->randomElement(['OPEN', 'PAID']),
            'Order_Date' => $this->faker->dateTime(),
            'Transaction_Hash' => $this->faker->uuid(), // Beispiel für zufälligen Hash
        ];
    }
}
