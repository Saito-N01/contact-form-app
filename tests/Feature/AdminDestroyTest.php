<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_レコードが正常に削除される(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $this->actingAs($user)->delete("/admin/contacts/{$contact->id}");

        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_削除後はadminにリダイレクトされる(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($user)->delete("/admin/contacts/{$contact->id}");

        $response->assertRedirect('/admin');
    }

    public function test_タグが紐付いていても中間テーブルごと削除される(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();
        $contact = Contact::factory()->create();
        $contact->tags()->attach($tag->id);

        $this->actingAs($user)->delete("/admin/contacts/{$contact->id}");

        $this->assertDatabaseMissing('contact_tag', ['contact_id' => $contact->id]);
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }

    public function test_存在しない_i_dの場合は404が返る(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/admin/contacts/9999');

        $response->assertStatus(404);
    }

    public function test_未認証ユーザーは削除できない(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->delete("/admin/contacts/{$contact->id}");

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
    }
}
