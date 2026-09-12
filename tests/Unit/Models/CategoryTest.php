<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_1つのカテゴリから紐づく複数のお問い合わせが取得できる(): void
    {
        $category = Category::factory()->create();

        $contacts = Contact::factory()->count(3)->create([
            'category_id' => $category->id,
        ]);

        $this->assertCount(3, $category->contacts);
        $this->assertTrue(
            $category->contacts->pluck('id')->diff($contacts->pluck('id'))->isEmpty()
        );
    }

    public function test_別カテゴリのお問い合わせは含まれない(): void
    {
        $categoryA = Category::factory()->create();
        $categoryB = Category::factory()->create();

        Contact::factory()->count(2)->create(['category_id' => $categoryA->id]);
        Contact::factory()->count(1)->create(['category_id' => $categoryB->id]);

        $this->assertCount(2, $categoryA->contacts);
        $this->assertCount(1, $categoryB->contacts);
    }

    public function test_お問い合わせが0件のカテゴリはcontactsが空になる(): void
    {
        $category = Category::factory()->create();

        $this->assertCount(0, $category->contacts);
    }
}
