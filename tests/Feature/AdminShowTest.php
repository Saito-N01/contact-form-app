<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_指定したお問い合わせがカテゴリ情報付きで表示される(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['content' => '商品トラブル']);
        $contact = Contact::factory()->create([
            'first_name' => '太郎',
            'last_name' => '山田',
            'email' => 'taro@example.com',
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)->get("/admin/contacts/{$contact->id}");

        $response->assertStatus(200);
        $response->assertViewIs('admin.show');
        $response->assertSee('山田');
        $response->assertSee('太郎');
        $response->assertSee('taro@example.com');
        $response->assertSee('商品トラブル');
    }

    public function test_タグが紐付いている場合はタグ名も表示される(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['name' => '不具合報告']);
        $contact = Contact::factory()->create();
        $contact->tags()->attach($tag->id);

        $response = $this->actingAs($user)->get("/admin/contacts/{$contact->id}");

        $response->assertSee('不具合報告');
    }

    public function test_showビューにcontactが渡される(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($user)->get("/admin/contacts/{$contact->id}");

        $response->assertViewHas('contact', function ($viewContact) use ($contact) {
            return $viewContact->is($contact);
        });
    }

    public function test_存在しない_i_dの場合は404が返る(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/contacts/9999');

        $response->assertStatus(404);
    }

    public function test_未認証ユーザーはアクセスできない(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->get("/admin/contacts/{$contact->id}");

        $response->assertRedirect('/login');
    }
}
