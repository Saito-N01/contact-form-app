<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_条件なしで一覧取得でき構造が正しい(): void
    {
        Contact::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/contacts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'category', 'first_name', 'last_name', 'gender', 'email', 'tel', 'address', 'building', 'detail', 'tags', 'created_at', 'updated_at'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
    }

    public function test_keywordで氏名を検索できる(): void
    {
        Contact::factory()->create(['first_name' => '太郎', 'last_name' => '山田']);
        Contact::factory()->create(['first_name' => '花子', 'last_name' => '鈴木']);

        $response = $this->getJson('/api/v1/contacts?keyword=山田太郎');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.last_name', '山田');
    }

    public function test_genderで絞り込みできる(): void
    {
        Contact::factory()->create(['gender' => 1]);
        Contact::factory()->create(['gender' => 2]);

        $response = $this->getJson('/api/v1/contacts?gender=1');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.gender', 1);
    }

    public function test_category_idで絞り込みできる(): void
    {
        $categoryA = Category::factory()->create();
        $categoryB = Category::factory()->create();
        Contact::factory()->create(['category_id' => $categoryA->id]);
        Contact::factory()->create(['category_id' => $categoryB->id]);

        $response = $this->getJson("/api/v1/contacts?category_id={$categoryA->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.category.id', $categoryA->id);
    }

    public function test_dateで絞り込みできる(): void
    {
        $contactToday = Contact::factory()->create();
        $contactToday->created_at = now();
        $contactToday->save();

        $contactYesterday = Contact::factory()->create();
        $contactYesterday->created_at = now()->subDay();
        $contactYesterday->save();

        $response = $this->getJson('/api/v1/contacts?date='.now()->format('Y-m-d'));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $contactToday->id);
    }

    public function test_per_pageで件数を指定できる(): void
    {
        Contact::factory()->count(15)->create();

        $response = $this->getJson('/api/v1/contacts?per_page=5');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
        $response->assertJsonPath('meta.per_page', 5);
        $response->assertJsonPath('meta.total', 15);
    }

    public function test_per_page未指定時はデフォルト20件で返る(): void
    {
        Contact::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/contacts');

        $response->assertStatus(200);
        $response->assertJsonCount(20, 'data');
        $response->assertJsonPath('meta.per_page', 20);
    }

    public function test_pageで2ページ目を取得できる(): void
    {
        Contact::factory()->count(15)->create();

        $response = $this->getJson('/api/v1/contacts?per_page=10&page=2');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
        $response->assertJsonPath('meta.current_page', 2);
    }

    public function test_tagsが配列で返る(): void
    {
        $contact = Contact::factory()->create();
        $tag = Tag::factory()->create(['name' => '質問']);
        $contact->tags()->attach($tag->id);

        $response = $this->getJson('/api/v1/contacts');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.tags.0.name', '質問');
    }

    public function test_不正なgenderを指定すると422が返る(): void
    {
        $response = $this->getJson('/api/v1/contacts?gender=9');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('gender');
    }

    public function test_不正なper_pageを指定すると422が返る(): void
    {
        $response = $this->getJson('/api/v1/contacts?per_page=101');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('per_page');
    }
}
