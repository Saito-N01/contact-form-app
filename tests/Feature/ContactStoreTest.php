<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactStoreTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_バリデーション通過時にcontactsテーブルに保存される(): void
    {
        $data = $this->validData();

        $this->post('/contacts', $data);

        $this->assertDatabaseHas('contacts', [
            'first_name' => '太郎',
            'last_name' => '山田',
            'email' => 'taro@example.com',
            'tel' => '09012345678',
        ]);
    }

    public function test_タグがcontact_tagテーブルに記録される(): void
    {
        $tags = Tag::factory()->count(2)->create();
        $data = $this->validData(['tag_ids' => $tags->pluck('id')->toArray()]);

        $this->post('/contacts', $data);

        $contact = Contact::where('email', 'taro@example.com')->firstOrFail();

        foreach ($tags as $tag) {
            $this->assertDatabaseHas('contact_tag', [
                'contact_id' => $contact->id,
                'tag_id' => $tag->id,
            ]);
        }
    }

    public function test_タグ未選択でも保存される(): void
    {
        $data = $this->validData(['tag_ids' => null]);

        $this->post('/contacts', $data);

        $contact = Contact::where('email', 'taro@example.com')->firstOrFail();

        $this->assertCount(0, $contact->tags);
    }

    public function test_保存成功後はthanksへリダイレクトされる(): void
    {
        $data = $this->validData();

        $response = $this->post('/contacts', $data);

        $response->assertRedirect('/thanks');
    }

    public function test_バリデーションエラー時はリダイレクトされエラーが返る(): void
    {
        $data = $this->validData(['tel' => '090-1234-5678']);

        $response = $this->post('/contacts', $data);

        $response->assertSessionHasErrors('tel');
    }

    public function test_バリデーションエラー時はレコードが保存されない(): void
    {
        $data = $this->validData(['email' => '']);

        $this->post('/contacts', $data);

        $this->assertDatabaseMissing('contacts', ['tel' => $data['tel']]);
    }
}
