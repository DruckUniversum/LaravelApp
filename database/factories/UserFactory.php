<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'First_Name' => $this->faker->firstName(),
            'Last_Name' => $this->faker->lastName(),
            'Street' => $this->faker->streetName(),
            'House_Number' => $this->faker->buildingNumber(),
            'Country' => $this->faker->country(),
            'City' => $this->faker->city(),
            'Postal_Code' => $this->faker->postcode(),
            'Email' => $this->faker->unique()->safeEmail(),
            'Password_Hash' => bcrypt('password'), // Standard-Passwort
            'AGB_Akzeptiert' => true,
            'Last_Login' => $this->faker->optional()->dateTime(),
            'Failed_Logins' => $this->faker->numberBetween(0, 5),
        ];
    }
}
