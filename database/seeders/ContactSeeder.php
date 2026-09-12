<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryIds = Category::pluck('id')->toArray();

        Contact::factory()
            ->count(20)
            ->create(['category_id' => fn () => fake()->randomElement($categoryIds)])
            ->each(function ($contact) {
                $tags = Tag::inRandomOrder()->take(rand(1, 3))->pluck('id');
                $contact->tags()->attach($tags);
            });
    }
}
