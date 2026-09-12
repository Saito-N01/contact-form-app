<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use App\Services\ContactSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_keywordで氏名を検索できる(): void
    {
        $user = User::factory()->create();
        Contact::factory()->create(['first_name' => '太郎', 'last_name' => '山田']);
        Contact::factory()->create(['first_name' => '花子', 'last_name' => '鈴木']);

        $response = $this->actingAs($user)->get('/admin?keyword=山田太郎');

        $response->assertSee('山田');
        $response->assertDontSee('鈴木');
    }

    public function test_keywordでメールアドレスを検索できる(): void
    {
        $user = User::factory()->create();
        Contact::factory()->create(['email' => 'taro@example.com']);
        Contact::factory()->create(['email' => 'hanako@example.com']);

        $response = $this->actingAs($user)->get('/admin?keyword=taro@example.com');

        $response->assertSee('taro@example.com');
        $response->assertDontSee('hanako@example.com');
    }

    public function test_genderで絞り込みできる(): void
    {
        $user = User::factory()->create();
        Contact::factory()->create(['first_name' => '男性太郎', 'gender' => 1]);
        Contact::factory()->create(['first_name' => '女性花子', 'gender' => 2]);

        $response = $this->actingAs($user)->get('/admin?gender=1');

        $response->assertSee('男性太郎');
        $response->assertDontSee('女性花子');
    }

    public function test_genderが0の場合は全件表示される(): void
    {
        $user = User::factory()->create();
        Contact::factory()->create(['first_name' => '男性太郎', 'gender' => 1]);
        Contact::factory()->create(['first_name' => '女性花子', 'gender' => 2]);

        $response = $this->actingAs($user)->get('/admin?gender=0');

        $response->assertSee('男性太郎');
        $response->assertSee('女性花子');
    }

    /** category_idで絞り込みできる */
    public function test_category_idで絞り込みできる(): void
    {
        $user = User::factory()->create();
        $categoryA = Category::factory()->create();
        $categoryB = Category::factory()->create();
        Contact::factory()->create(['first_name' => 'カテゴリA太郎', 'category_id' => $categoryA->id]);
        Contact::factory()->create(['first_name' => 'カテゴリB花子', 'category_id' => $categoryB->id]);

        $response = $this->actingAs($user)->get('/admin?category_id='.$categoryA->id);

        $response->assertSee('カテゴリA太郎');
        $response->assertDontSee('カテゴリB花子');
    }

    public function test_dateで絞り込みできる(): void
    {
        $user = User::factory()->create();

        $contactToday = Contact::factory()->create(['first_name' => '本日太郎']);
        $contactToday->created_at = now();
        $contactToday->save();

        $contactYesterday = Contact::factory()->create(['first_name' => '昨日花子']);
        $contactYesterday->created_at = now()->subDay();
        $contactYesterday->save();

        $response = $this->actingAs($user)->get('/admin?date='.now()->format('Y-m-d'));

        $response->assertSee('本日太郎');
        $response->assertDontSee('昨日花子');
    }

    public function test_全ての検索条件を指定した状態で絞り込みできる(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $target = Contact::factory()->create([
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
        ]);
        $target->created_at = now();
        $target->save();

        Contact::factory()->create(['first_name' => '花子', 'last_name' => '鈴木', 'gender' => 2]);

        $response = $this->actingAs($user)->get('/admin?'.http_build_query([
            'keyword' => '山田太郎',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => now()->format('Y-m-d'),
        ]));

        $response->assertSee('山田');
        $response->assertDontSee('鈴木');
    }

    public function test_結果が7件ごとにページネーションされる(): void
    {
        $user = User::factory()->create();
        Contact::factory()->count(10)->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertViewHas('contacts', function ($contacts) {
            return $contacts->count() === 7 && $contacts->total() === 10;
        });
    }

    public function test_2ページ目には残りの件数が表示される(): void
    {
        $user = User::factory()->create();
        Contact::factory()->count(10)->create();

        $response = $this->actingAs($user)->get('/admin?page=2');

        $response->assertViewHas('contacts', function ($contacts) {
            return $contacts->count() === 3;
        });
    }

    public function test_keyword検索で姓名どちらの並びでもヒットする(): void
    {
        Contact::factory()->create(['first_name' => '太郎', 'last_name' => '山田']);

        $service = new ContactSearchService;
        $results = $service->search(['keyword' => '山田太郎'])->get();

        $this->assertCount(1, $results);
    }
}
