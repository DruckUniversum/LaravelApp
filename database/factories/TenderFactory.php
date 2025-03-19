<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Order;

class TenderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'Status' => $this->faker->randomElement(['OPEN', 'ACCEPTED', 'PAID', 'PROCESSING', 'SHIPPING', 'CLOSED']),
            'Bid' => $this->faker->randomFloat(2, 50, 1000), // Zufälliger Gebotspreis
            'Infill' => $this->faker->numberBetween(10, 100), // Zufälliger %-Wert
            'Filament' => $this->faker->randomElement(['PLA', 'ABS', 'Carbon']),
            'Description' => $this->faker->paragraph(),
            'Tenderer_ID' => User::factory(), // Erstellt Tenderer-Referenz
            'Provider_ID' => User::factory(), // Erstellt Provider-Referenz
            'Order_ID' => Order::factory(), // Erstellt Order-Referenz
            'Tender_Date' => $this->faker->dateTime(),
            'Shipping_Provider' => $this->faker->company(),
            'Shipping_Number' => $this->faker->bothify('##??##??'),
            'Transaction_Hash' => $this->faker->uuid(),
        ];
    }
}
