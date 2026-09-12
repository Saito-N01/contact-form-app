<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_特定のカテゴリに属している(): void
    {
        $category = Category::factory()->create();
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $contact->category);
        $this->assertTrue($contact->category->is($category));
    }

    public function test_categoryリレーションの型がbelongs_toである(): void
    {
        $contact = Contact::factory()->create();

        $this->assertInstanceOf(
            BelongsTo::class,
            $contact->category()
        );
    }

    public function test_複数のタグと同期できる(): void
    {
        $contact = Contact::factory()->create();
        $tags = Tag::factory()->count(3)->create();

        $contact->tags()->sync($tags->pluck('id'));

        $this->assertCount(3, $contact->tags()->get());
        $this->assertTrue(
            $contact->tags()->get()->pluck('id')->diff($tags->pluck('id'))->isEmpty()
        );
    }

    public function test_syncで再度呼び出すとタグが置き換わる(): void
    {
        $contact = Contact::factory()->create();
        $oldTags = Tag::factory()->count(2)->create();
        $newTags = Tag::factory()->count(2)->create();

        $contact->tags()->sync($oldTags->pluck('id'));
        $contact->tags()->sync($newTags->pluck('id'));

        $currentTagIds = $contact->tags()->get()->pluck('id');

        $this->assertCount(2, $currentTagIds);
        $this->assertTrue($currentTagIds->diff($newTags->pluck('id'))->isEmpty());
        $this->assertTrue($currentTagIds->intersect($oldTags->pluck('id'))->isEmpty());
    }

    public function test_syncで空配列を渡すと全てのタグが解除される(): void
    {
        $contact = Contact::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $contact->tags()->sync($tags->pluck('id'));
        $contact->tags()->sync([]);

        $this->assertCount(0, $contact->tags()->get());
    }
}
