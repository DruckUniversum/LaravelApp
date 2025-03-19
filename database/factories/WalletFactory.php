<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class WalletFactory extends Factory
{
    public function definition(): array
    {
        return [
            'Address' => $this->faker->unique()->uuid(),
            'Coin_Symbol' => 'BCY', // Standardwert
            'Pub_Key' => $this->faker->sha256(),
            'Priv_Key' => $this->faker->sha256(),
            'User_ID' => User::factory(), // User-Referenz
        ];
    }
}
