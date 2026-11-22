<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'user_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => fake()->phoneNumber(),
            'residence' => fake()->address(),
            'url_img' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQemcwXK2PydnUgOUCRNKUAU5f6Gu_mTVUGnCHHOosOrvIEOBykfRzqtlE&s=10',
        ];
    }
}
