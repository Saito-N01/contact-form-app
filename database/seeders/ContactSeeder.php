<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Contact;
use App\Models\Tag;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Contact::factory()->count(20)->create()->each(function ($contact) {
            $tags = Tag::inRandomOrder()->take(rand(1, 3))->pluck('id');
            $contact->tags()->attach($tags);
        });
    }
}
