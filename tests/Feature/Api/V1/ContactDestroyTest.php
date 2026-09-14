<?php

namespace Tests\Feature\Api\V1;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_有効な_i_dを指定すると204が返る(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->deleteJson("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(204);
        $response->assertNoContent();
    }

    public function test_contactsテーブルから削除される(): void
    {
        $contact = Contact::factory()->create();

        $this->deleteJson("/api/v1/contacts/{$contact->id}");

        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_タグが紐付いていても中間テーブルごと削除される(): void
    {
        $tag = Tag::factory()->create();
        $contact = Contact::factory()->create();
        $contact->tags()->attach($tag->id);

        $this->deleteJson("/api/v1/contacts/{$contact->id}");

        $this->assertDatabaseMissing('contact_tag', ['contact_id' => $contact->id]);
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }

    public function test_存在しない_i_dの場合は404が返る(): void
    {
        $response = $this->deleteJson('/api/v1/contacts/999999');

        $response->assertStatus(404);
        $response->assertExactJson([
            'error' => 'お問い合わせが見つかりませんでした。',
        ]);
    }

    public function test_既に削除済みの_i_dに再度削除リクエストを送ると404が返る(): void
    {
        $contact = Contact::factory()->create();

        $this->deleteJson("/api/v1/contacts/{$contact->id}")->assertStatus(204);
        $response = $this->deleteJson("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(404);
    }
}
