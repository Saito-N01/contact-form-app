<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済みユーザーは編集画面を表示できる(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['name' => '質問']);

        $response = $this->actingAs($user)->get("/admin/tags/{$tag->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('admin.tags.edit');
        $response->assertSee('質問');
    }

    public function test_タグを作成でき作成後はadminへリダイレクトされる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/tags', ['name' => '新機能の要望']);

        $response->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', ['name' => '新機能の要望']);
    }

    public function test_タグ名が不正な場合は作成されずエラーが返る(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/tags', ['name' => '']);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('tags', ['name' => '']);
    }

    public function test_タグを更新でき更新後はadminへリダイレクトされる(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['name' => '質問']);

        $response = $this->actingAs($user)->put("/admin/tags/{$tag->id}", ['name' => '要望']);

        $response->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => '要望']);
    }

    public function test_他のタグと重複する名前には更新できない(): void
    {
        $user = User::factory()->create();
        Tag::factory()->create(['name' => '要望']);
        $tag = Tag::factory()->create(['name' => '質問']);

        $response = $this->actingAs($user)->put("/admin/tags/{$tag->id}", ['name' => '要望']);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => '質問']);
    }

    /** タグを削除でき、削除後は/adminへリダイレクトされる */
    public function test_タグを削除でき削除後はadminへリダイレクトされる(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($user)->delete("/admin/tags/{$tag->id}");

        $response->assertRedirect('/admin');
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_未認証ユーザーは編集画面にアクセスできない(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->get("/admin/tags/{$tag->id}/edit");

        $response->assertRedirect('/login');
    }

    public function test_未認証ユーザーはタグを作成できない(): void
    {
        $response = $this->post('/admin/tags', ['name' => '新機能の要望']);

        $response->assertRedirect('/login');
        $this->assertDatabaseMissing('tags', ['name' => '新機能の要望']);
    }

    public function test_未認証ユーザーはタグを更新できない(): void
    {
        $tag = Tag::factory()->create(['name' => '質問']);

        $response = $this->put("/admin/tags/{$tag->id}", ['name' => '要望']);

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => '質問']);
    }

    public function test_未認証ユーザーはタグを削除できない(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->delete("/admin/tags/{$tag->id}");

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }
}
