<?php

namespace Tests\Unit;

use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateTagRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * UpdateTagRequestのrules()は $this->route('tag') で
     * 更新対象のTagモデルを参照するため、ルートパラメータを
     * 差し込んだ状態でルールを取得するヘルパー。
     */
    private function rulesFor(Tag $tag): array
    {
        $request = new UpdateTagRequest;

        $request->setRouteResolver(function () use ($tag) {
            return new class($tag)
            {
                public function __construct(private Tag $tag) {}

                public function parameter($name, $default = null)
                {
                    return $this->tag;
                }
            };
        });

        return $request->rules();
    }

    public function test_他と重複しない新しい名前であれば通過する(): void
    {
        $tag = Tag::factory()->create(['name' => '質問']);

        $validator = Validator::make(['name' => '新しい名前'], $this->rulesFor($tag));

        $this->assertTrue($validator->passes());
    }

    public function test_自身の現在の名前を維持する場合は通過する(): void
    {
        $tag = Tag::factory()->create(['name' => '質問']);

        $validator = Validator::make(['name' => '質問'], $this->rulesFor($tag));

        $this->assertTrue($validator->passes());
    }

    public function test_他のタグで使用中の名前への変更は拒否される(): void
    {
        Tag::factory()->create(['name' => '要望']);
        $tag = Tag::factory()->create(['name' => '質問']);

        $validator = Validator::make(['name' => '要望'], $this->rulesFor($tag));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_タグ名が未入力の場合は拒否される(): void
    {
        $tag = Tag::factory()->create(['name' => '質問']);

        $validator = Validator::make(['name' => ''], $this->rulesFor($tag));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_タグ名が50文字を超えると拒否される(): void
    {
        $tag = Tag::factory()->create(['name' => '質問']);

        $data = ['name' => str_repeat('あ', 51)];

        $validator = Validator::make($data, $this->rulesFor($tag));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_タグ名がちょうど50文字なら通過する(): void
    {
        $tag = Tag::factory()->create(['name' => '質問']);

        $data = ['name' => str_repeat('あ', 50)];

        $validator = Validator::make($data, $this->rulesFor($tag));

        $this->assertTrue($validator->passes());
    }
}
