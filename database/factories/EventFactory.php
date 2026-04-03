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
        $isFree = $this->faker->boolean();
        return [
            'title' => $title,
            'description' => fake()->paragraph(),
            'location' => fake()->randomElement([$location, 'Remote']),
            'capacity' => fake()->numberBetween(70, 100),
            // Start the date in the future so it passes your "after:today" validation
            'date' => $this->faker->dateTimeBetween('+1 week', '+1 year'),
            'banner' => $this->faker->randomElement([
                'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800&h=400&fit=crop', // Tech Conference
                'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=800&h=400&fit=crop', // Crowded Event
                'https://images.unsplash.com/photo-1556761175-5973dc0f32e7?w=800&h=400&fit=crop', // Coding Meetup
                'https://images.unsplash.com/photo-1475721028070-966fb9c07fb3?w=800&h=400&fit=crop', // Keynote Speaker
                'https://images.unsplash.com/photo-1515187029135-18ee286d815b?w=800&h=400&fit=crop', // Meeting Room
            ]),
            'is_free' => $isFree,
            // If it's free, price is null. If paid, generate a random price between 5k and 50k
            'price' => $isFree ? null : $this->faker->randomFloat(2, 5000, 50000),
            'organizer_id' => fake()->randomElement(['9da3578e-d50a-45ed-af5a-aa27b4d9ee29', 'ba0818db-343c-4b2f-b20b-3ff3055b3b8e'])
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
    public function free(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_free' => true,
            'price' => null,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_free' => false,
            'price' => $this->faker->randomFloat(2, 5000, 50000),
        ]);
    }
}
