<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_1つのタグが複数のお問い合わせに紐づいている(): void
    {
        $tag = Tag::factory()->create();
        $contacts = Contact::factory()->count(3)->create();

        foreach ($contacts as $contact) {
            $contact->tags()->attach($tag->id);
        }

        $this->assertCount(3, $tag->contacts);
        $this->assertTrue(
            $tag->contacts->pluck('id')->diff($contacts->pluck('id'))->isEmpty()
        );
    }

    public function test_紐付いていないお問い合わせは含まれない(): void
    {
        $tag = Tag::factory()->create();
        $attachedContact = Contact::factory()->create();
        $unattachedContact = Contact::factory()->create();

        $attachedContact->tags()->attach($tag->id);

        $this->assertCount(1, $tag->contacts);
        $this->assertTrue($tag->contacts->first()->is($attachedContact));
    }

    public function test_紐付くお問い合わせが0件のタグはcontactsが空になる(): void
    {
        $tag = Tag::factory()->create();

        $this->assertCount(0, $tag->contacts);
    }

    public function test_タグ削除時に中間テーブルの関連レコードも削除される(): void
    {
        $tag = Tag::factory()->create();
        $contact = Contact::factory()->create();
        $contact->tags()->attach($tag->id);

        $this->assertDatabaseHas('contact_tag', ['tag_id' => $tag->id, 'contact_id' => $contact->id]);

        $tag->delete();

        $this->assertDatabaseMissing('contact_tag', ['tag_id' => $tag->id, 'contact_id' => $contact->id]);
    }
}
