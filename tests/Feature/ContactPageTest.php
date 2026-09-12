<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_お問い合わせフォーム入力ページが表示される(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('contact.index');
    }

    public function test_categoriesとtagsがビュー変数として渡される(): void
    {
        $response = $this->get('/');

        $response->assertViewHasAll(['categories', 'tags']);
    }

    public function test_カテゴリ名がページに表示される(): void
    {
        $category = Category::factory()->create(['content' => '商品トラブル']);

        $response = $this->get('/');

        $response->assertSee('商品トラブル');
        $response->assertViewHas('categories', function ($categories) use ($category) {
            return $categories->contains('id', $category->id);
        });
    }

    public function test_タグ名がページに表示される(): void
    {
        $tag = Tag::factory()->create(['name' => '不具合報告']);

        $response = $this->get('/');

        $response->assertSee('不具合報告');
        $response->assertViewHas('tags', function ($tags) use ($tag) {
            return $tags->contains('id', $tag->id);
        });
    }

    public function test_サンクスページが表示される(): void
    {
        $response = $this->get('/thanks');

        $response->assertStatus(200);
        $response->assertViewIs('contact.thanks');
        $response->assertSee('お問い合わせありがとうございました');
    }
}
