<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactUpdateTest extends TestCase
{
    use RefreshDatabase;

    /** 有効なデータ一式を返すヘルパー */
    private function validData(array $overrides = []): array
    {
        $category = Category::factory()->create();

        return array_merge([
            'first_name' => '次郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'jiro@example.com',
            'tel' => '08087654321',
            'address' => '東京都渋谷区千駄ヶ谷1-2-3',
            'building' => '',
            'category_id' => $category->id,
            'detail' => '内容を更新しました。',
            'tag_ids' => [],
        ], $overrides);
    }

    public function test_有効なデータで更新でき200が返る(): void
    {
        $contact = Contact::factory()->create(['first_name' => '太郎']);
        $data = $this->validData();

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", $data);

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $contact->id);
        $response->assertJsonPath('data.first_name', '次郎');
        $response->assertJsonPath('data.email', 'jiro@example.com');
    }

    public function test_contactsテーブルの値が更新される(): void
    {
        $contact = Contact::factory()->create(['first_name' => '太郎']);
        $data = $this->validData();

        $this->putJson("/api/v1/contacts/{$contact->id}", $data);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'first_name' => '次郎',
            'email' => 'jiro@example.com',
        ]);
    }

    public function test_tag_idsを指定すると以前のタグが新しいタグに置き換わる(): void
    {
        $oldTag = Tag::factory()->create(['name' => '旧タグ']);
        $newTag = Tag::factory()->create(['name' => '新タグ']);

        $contact = Contact::factory()->create();
        $contact->tags()->attach($oldTag->id);

        $data = $this->validData(['tag_ids' => [$newTag->id]]);

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", $data);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.tags');
        $response->assertJsonPath('data.tags.0.name', '新タグ');

        $this->assertDatabaseMissing('contact_tag', ['contact_id' => $contact->id, 'tag_id' => $oldTag->id]);
        $this->assertDatabaseHas('contact_tag', ['contact_id' => $contact->id, 'tag_id' => $newTag->id]);
    }

    public function test_tag_ids省略時は全てのタグが解除される(): void
    {
        $tag = Tag::factory()->create();
        $contact = Contact::factory()->create();
        $contact->tags()->attach($tag->id);

        $data = $this->validData(['tag_ids' => null]);

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", $data);

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data.tags');
        $this->assertDatabaseMissing('contact_tag', ['contact_id' => $contact->id]);
    }

    public function test_存在しない_i_dの場合は404が返る(): void
    {
        $data = $this->validData();

        $response = $this->putJson('/api/v1/contacts/999999', $data);

        $response->assertStatus(404);
        $response->assertExactJson([
            'error' => 'お問い合わせが見つかりませんでした。',
        ]);
    }

    public function test_必須項目が未入力の場合は422が返る(): void
    {
        $contact = Contact::factory()->create();
        $data = $this->validData(['email' => '']);

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    public function test_バリデーションエラー時は値が更新されない(): void
    {
        $contact = Contact::factory()->create(['first_name' => '太郎']);
        $data = $this->validData(['email' => '']);

        $this->putJson("/api/v1/contacts/{$contact->id}", $data);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'first_name' => '太郎',
        ]);
    }
}
