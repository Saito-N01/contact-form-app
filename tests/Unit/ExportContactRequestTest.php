<?php

namespace Tests\Unit;

use App\Http\Requests\ExportContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ExportContactRequestTest extends TestCase
{
    use RefreshDatabase;

    private array $rules;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rules = (new ExportContactRequest)->rules();
    }

    public function test_検索条件が何も無くても通過する(): void
    {
        $validator = Validator::make([], $this->rules);

        $this->assertTrue($validator->passes());
    }

    public function test_全ての検索条件を指定した状態で通過する(): void
    {
        $category = Category::factory()->create();

        $data = [
            'keyword' => '山田太郎',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-09-01',
        ];

        $validator = Validator::make($data, $this->rules);

        $this->assertTrue($validator->passes());
    }

    public function test_不正なgenderの値は拒否される(): void
    {
        $validator = Validator::make(['gender' => 9], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
    }

    public function test_存在しないカテゴリ_i_dは拒否される(): void
    {
        $validator = Validator::make(['category_id' => 9999], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
    }

    public function test_不正な日付形式は拒否される(): void
    {
        $validator = Validator::make(['date' => '2026-13-40'], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('date', $validator->errors()->toArray());
    }
}
