<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
        'name' => fake()->name(),
        'user_id' => User::query()->inRandomOrder()->value("id") ?? User::factory(),
        'phone_number' => fake()->phoneNumber(),
        'residence' => fake()->address()
        ];
    }
}
