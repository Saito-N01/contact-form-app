<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactConfirmTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        $category = Category::factory()->create(['content' => '商品トラブル']);
        $tag = Tag::factory()->create(['name' => '不具合報告']);

        return array_merge([
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'tel1' => '090',
            'tel2' => '1234',
            'tel3' => '5678',
            'address' => '東京都渋谷区千駄ヶ谷1-2-3',
            'building' => 'サンプルマンション101',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容のテストです。',
            'tag_ids' => [$tag->id],
        ], $overrides);
    }

    public function test_バリデーション通過時に確認ページが表示され入力内容が表示される(): void
    {
        $data = $this->validData();

        $response = $this->post('/contacts/confirm', $data);

        $response->assertStatus(200);
        $response->assertViewIs('contact.confirm');
        $response->assertSee('山田');
        $response->assertSee('太郎');
        $response->assertSee('taro@example.com');
        $response->assertSee('商品トラブル');
        $response->assertSee('不具合報告');
    }

    public function test_confirmビューに必要な変数が渡される(): void
    {
        $data = $this->validData();

        $response = $this->post('/contacts/confirm', $data);

        $response->assertViewHasAll(['validated', 'category', 'tags']);
    }

    public function test_バリデーションエラー時はリダイレクトされエラーが返る(): void
    {
        $data = $this->validData(['email' => '']);

        $response = $this->post('/contacts/confirm', $data);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('email');
    }

    public function test_不正な電話番号形式の場合もエラーになる(): void
    {
        $data = $this->validData(['tel' => '090-1234-5678']);

        $response = $this->post('/contacts/confirm', $data);

        $response->assertSessionHasErrors('tel');
    }

    public function test_建物名が未入力でも確認ページが表示される(): void
    {
        $data = $this->validData(['building' => '']);

        $response = $this->post('/contacts/confirm', $data);

        $response->assertStatus(200);
        $response->assertViewIs('contact.confirm');
    }
}
