<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence();
        $location = $this->faker->city();
        return [
            'title' => $title,
            'description' => fake()->paragraph(),
            'location' => fake()->randomElement([$location, 'Remote']),
            'capacity' => fake()->numberBetween(70, 100),
            'date' => fake()->dateTimeBetween('now', '+1 year'),
            'organizer_id'=>fake()->randomElement(['9d4ed36d-8313-478e-b734-f1db90930325', 'ad0a3d84-55ea-4d26-9497-0f46f241e37f'])
        ];
    }
    public function onsite(): static
    {
        return $this->state(fn(array $attributes) => [
            'location' => $this->faker->city(),
        ]);
    }
    public function remote(): static
    {
        return $this->state(fn(array $attributes) => [
            'location' => 'Remote',
        ]);
    }
}
