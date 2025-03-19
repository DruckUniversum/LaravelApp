<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Category;
use App\Models\User;

class DesignFactory extends Factory
{
    public function definition(): array
    {
        return [
            'Name' => $this->faker->words(3, true),
            'STL_File' => $this->faker->filePath(), // Generiere ein Datei-Pfad
            'Price' => $this->faker->randomFloat(2, 10, 500), // Zufälliger Preis zwischen 10 und 500
            'Description' => $this->faker->paragraph(),
            'Cover_Picture_File' => $this->faker->imageUrl(),
            'License' => $this->faker->text(50),
            'Category_ID' => Category::factory(), // Erstellt eine Category-Referenz
            'Designer_ID' => User::factory(), // Erstellt eine Designer-Referenz
        ];
    }
}
