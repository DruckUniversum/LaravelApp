<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Tender;

class ChatFactory extends Factory
{
    public function definition(): array
    {
        return [
            'Tender_ID' => Tender::factory(), // Erstellt eine Tender-Referenz
        ];
    }
}
