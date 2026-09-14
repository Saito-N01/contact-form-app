<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_cs_vがダウンロードでき_content_typeが正しい(): void
    {
        $user = User::factory()->create();
        Contact::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/contacts/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition');
    }

    public function test_cs_vの先頭に_bo_mが付与されている(): void
    {
        $user = User::factory()->create();
        Contact::factory()->count(1)->create();

        $content = $this->actingAs($user)->get('/contacts/export')->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
    }

    public function test_ヘッダー行の列順が仕様通りである(): void
    {
        $user = User::factory()->create();
        Contact::factory()->count(1)->create();

        $content = $this->actingAs($user)->get('/contacts/export')->streamedContent();
        $csv = mb_convert_encoding(ltrim($content, "\xEF\xBB\xBF"), 'UTF-8', 'UTF-8');
        $lines = explode("\n", trim($csv));
        $header = str_getcsv($lines[0]);

        $this->assertSame(
            ['ID', '氏名', '性別', 'メール', '電話', '住所', '建物', 'カテゴリ', '内容', '作成日時'],
            $header
        );
    }

    public function test_データ行の内容が正しい(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['content' => '商品トラブル']);
        Contact::factory()->create([
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'taro@example.com',
            'category_id' => $category->id,
        ]);

        $content = $this->actingAs($user)->get('/contacts/export')->streamedContent();
        $csv = ltrim($content, "\xEF\xBB\xBF");
        $lines = explode("\n", trim($csv));
        $row = str_getcsv($lines[1]);

        $this->assertSame('山田 太郎', $row[1]);
        $this->assertSame('男性', $row[2]);
        $this->assertSame('taro@example.com', $row[3]);
        $this->assertSame('商品トラブル', $row[7]);
    }

    public function test_keyword検索がエクスポート結果に反映される(): void
    {
        $user = User::factory()->create();
        Contact::factory()->create(['first_name' => '太郎', 'last_name' => '山田']);
        Contact::factory()->create(['first_name' => '花子', 'last_name' => '鈴木']);

        $content = $this->actingAs($user)->get('/contacts/export?keyword=山田太郎')->streamedContent();
        $csv = ltrim($content, "\xEF\xBB\xBF");
        $lines = array_filter(explode("\n", trim($csv)));

        $this->assertCount(2, $lines);
        $this->assertStringContainsString('山田', $lines[1]);
    }

    public function test_gender検索がエクスポート結果に反映される(): void
    {
        $user = User::factory()->create();
        Contact::factory()->create(['first_name' => '男性太郎', 'gender' => 1]);
        Contact::factory()->create(['first_name' => '女性花子', 'gender' => 2]);

        $content = $this->actingAs($user)->get('/contacts/export?gender=1')->streamedContent();

        $this->assertStringContainsString('男性太郎', $content);
        $this->assertStringNotContainsString('女性花子', $content);
    }

    public function test_検索条件未指定時は全件出力される(): void
    {
        $user = User::factory()->create();
        Contact::factory()->count(5)->create();

        $content = $this->actingAs($user)->get('/contacts/export')->streamedContent();
        $csv = ltrim($content, "\xEF\xBB\xBF");
        $lines = array_filter(explode("\n", trim($csv)));

        $this->assertCount(6, $lines);
    }

    public function test_不正な検索条件を指定するとリダイレクトされエラーが返る(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/contacts/export?gender=9');

        $response->assertRedirect();
        $response->assertSessionHasErrors('gender');
    }

    public function test_未認証ユーザーはエクスポートできない(): void
    {
        Contact::factory()->count(3)->create();

        $response = $this->get('/contacts/export');

        $response->assertRedirect('/login');
    }
}
