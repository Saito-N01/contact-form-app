<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            '質問',
            '要望',
            '不具合報告',
            'ご意見',
            'その他',
        ];

        foreach ($tags as $name) {
            Tag::create([
                'name' => $name,
            ]);
        }
    }
}
