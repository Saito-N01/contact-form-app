<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_指定した_i_dのお問い合わせが取得できる(): void
    {
        $category = Category::factory()->create();
        $contact = Contact::factory()->create([
            'first_name' => '太郎',
            'last_name' => '山田',
            'email' => 'taro@example.com',
            'category_id' => $category->id,
        ]);

        $response = $this->getJson("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $contact->id);
        $response->assertJsonPath('data.first_name', '太郎');
        $response->assertJsonPath('data.last_name', '山田');
        $response->assertJsonPath('data.email', 'taro@example.com');
        $response->assertJsonPath('data.category.id', $category->id);
    }

    public function test_レスポンス構造にcategoryとtagsが含まれる(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->getJson("/api/v1/contacts/{$contact->id}");

        $response->assertJsonStructure([
            'data' => ['id', 'category' => ['id', 'content'], 'tags', 'created_at', 'updated_at'],
        ]);
    }

    public function test_紐付くタグが配列で返る(): void
    {
        $contact = Contact::factory()->create();
        $tag1 = Tag::factory()->create(['name' => '質問']);
        $tag2 = Tag::factory()->create(['name' => '要望']);
        $contact->tags()->attach([$tag1->id, $tag2->id]);

        $response = $this->getJson("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data.tags');
    }

    public function test_タグが無い場合はtagsが空配列で返る(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->getJson("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data.tags');
    }

    public function test_存在しない_i_dの場合は404とカスタムエラーメッセージが返る(): void
    {
        $response = $this->getJson('/api/v1/contacts/999999');

        $response->assertStatus(404);
        $response->assertExactJson([
            'error' => 'お問い合わせが見つかりませんでした。',
        ]);
    }
}
