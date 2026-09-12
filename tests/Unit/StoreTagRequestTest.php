<?php

namespace Tests\Unit;

use App\Http\Requests\StoreTagRequest;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreTagRequestTest extends TestCase
{
    use RefreshDatabase;

    private array $rules;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rules = (new StoreTagRequest)->rules();
    }

    public function test_有効なタグ名であれば通過する(): void
    {
        $validator = Validator::make(['name' => '新機能の要望'], $this->rules);

        $this->assertTrue($validator->passes());
    }

    public function test_タグ名が未入力の場合は拒否される(): void
    {
        $validator = Validator::make(['name' => ''], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_タグ名が50文字を超えると拒否される(): void
    {
        $data = ['name' => str_repeat('あ', 51)];

        $validator = Validator::make($data, $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_タグ名がちょうど50文字なら通過する(): void
    {
        $data = ['name' => str_repeat('あ', 50)];

        $validator = Validator::make($data, $this->rules);

        $this->assertTrue($validator->passes());
    }

    public function test_既に存在するタグ名は拒否される(): void
    {
        Tag::factory()->create(['name' => '質問']);

        $validator = Validator::make(['name' => '質問'], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_他のタグ名と重複しなければ通過する(): void
    {
        Tag::factory()->create(['name' => '質問']);

        $validator = Validator::make(['name' => '要望'], $this->rules);

        $this->assertTrue($validator->passes());
    }
}
