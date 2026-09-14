<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactStoreTest extends TestCase
{
    use RefreshDatabase;

    /** 有効なデータ一式を返すヘルパー */
    private function validData(array $overrides = []): array
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        return array_merge([
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区千駄ヶ谷1-2-3',
            'building' => 'サンプルマンション101',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容のテストです。',
            'tag_ids' => $tags->pluck('id')->toArray(),
        ], $overrides);
    }

    public function test_有効なデータで作成でき201が返る(): void
    {
        $data = $this->validData();

        $response = $this->postJson('/api/v1/contacts', $data);

        $response->assertStatus(201);
        $response->assertJsonPath('data.first_name', '太郎');
        $response->assertJsonPath('data.last_name', '山田');
        $response->assertJsonPath('data.email', 'taro@example.com');
        $response->assertJsonPath('data.category.id', $data['category_id']);
        $response->assertJsonCount(2, 'data.tags');
    }

    public function test_contactsテーブルに保存される(): void
    {
        $data = $this->validData();

        $this->postJson('/api/v1/contacts', $data);

        $this->assertDatabaseHas('contacts', [
            'first_name' => '太郎',
            'last_name' => '山田',
            'email' => 'taro@example.com',
        ]);
    }

    public function test_タグがcontact_tagテーブルに記録される(): void
    {
        $tags = Tag::factory()->count(2)->create();
        $data = $this->validData(['tag_ids' => $tags->pluck('id')->toArray()]);

        $this->postJson('/api/v1/contacts', $data);

        $contact = Contact::where('email', 'taro@example.com')->firstOrFail();

        foreach ($tags as $tag) {
            $this->assertDatabaseHas('contact_tag', [
                'contact_id' => $contact->id,
                'tag_id' => $tag->id,
            ]);
        }
    }

    public function test_タグ未指定でも作成できる(): void
    {
        $data = $this->validData(['tag_ids' => null]);

        $response = $this->postJson('/api/v1/contacts', $data);

        $response->assertStatus(201);
        $response->assertJsonCount(0, 'data.tags');
    }

    public function test_必須項目が未入力の場合は422が返る(): void
    {
        $data = $this->validData(['email' => '']);

        $response = $this->postJson('/api/v1/contacts', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    public function test_不正な電話番号形式の場合は422が返る(): void
    {
        $data = $this->validData(['tel' => '090-1234-5678']);

        $response = $this->postJson('/api/v1/contacts', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('tel');
    }

    public function test_複数項目が不正な場合それぞれのエラーが返る(): void
    {
        $data = $this->validData([
            'first_name' => '',
            'email' => 'not-an-email',
            'gender' => 9,
        ]);

        $response = $this->postJson('/api/v1/contacts', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['first_name', 'email', 'gender']);
    }

    public function test_存在しないcategory_idの場合は422が返る(): void
    {
        $data = $this->validData(['category_id' => 9999]);

        $response = $this->postJson('/api/v1/contacts', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('category_id');
    }

    public function test_バリデーションエラー時はレコードが保存されない(): void
    {
        $data = $this->validData(['email' => '']);

        $this->postJson('/api/v1/contacts', $data);

        $this->assertDatabaseMissing('contacts', ['tel' => $data['tel']]);
    }
}
