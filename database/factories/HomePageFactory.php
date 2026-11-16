<?php

namespace Database\Factories;

use App\Models\HomePage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomePage>
 */
class HomePageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => 1,
            'content' => HomePage::DEFAULT_CONTENT,
            'hero_image_path' => null,
        ];
    }
}
