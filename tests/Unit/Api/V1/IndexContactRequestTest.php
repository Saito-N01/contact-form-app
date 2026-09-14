<?php

namespace Tests\Unit\Api\V1;

use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexContactRequestTest extends TestCase
{
    use RefreshDatabase;

    private array $rules;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rules = (new IndexContactRequest)->rules();
    }

    public function test_検索条件が何も無くても通過する(): void
    {
        $validator = Validator::make([], $this->rules);

        $this->assertTrue($validator->passes());
    }

    /**
     * @dataProvider validGenderProvider
     */
    public function test_genderが1から3の場合は通過する(int $gender): void
    {
        $validator = Validator::make(['gender' => $gender], $this->rules);

        $this->assertTrue($validator->passes());
    }

    public static function validGenderProvider(): array
    {
        return [
            '1(男性)' => [1],
            '2(女性)' => [2],
            '3(その他)' => [3],
        ];
    }

    public function test_genderが0の場合は拒否される(): void
    {
        $validator = Validator::make(['gender' => 0], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
    }

    /**
     * @dataProvider invalidGenderProvider
     */
    public function test_不正なgenderの値は拒否される(mixed $gender): void
    {
        $validator = Validator::make(['gender' => $gender], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
    }

    public static function invalidGenderProvider(): array
    {
        return [
            '4(範囲外)' => [4],
            '文字列' => ['male'],
        ];
    }

    public function test_実在するcategory_idを指定した場合に通過する(): void
    {
        $category = Category::factory()->create();

        $validator = Validator::make(['category_id' => $category->id], $this->rules);

        $this->assertTrue($validator->passes());
    }

    public function test_存在しないcategory_idを指定した場合は拒否される(): void
    {
        $validator = Validator::make(['category_id' => 9999], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
    }

    public function test_正しい日付形式の場合に通過する(): void
    {
        $validator = Validator::make(['date' => '2026-09-01'], $this->rules);

        $this->assertTrue($validator->passes());
    }

    public function test_不正な日付形式は拒否される(): void
    {
        $validator = Validator::make(['date' => '2026-13-40'], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('date', $validator->errors()->toArray());
    }

    public function test_pageが1以上の整数であれば通過する(): void
    {
        $validator = Validator::make(['page' => 2], $this->rules);

        $this->assertTrue($validator->passes());
    }

    public function test_pageが0以下の場合は拒否される(): void
    {
        $validator = Validator::make(['page' => 0], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('page', $validator->errors()->toArray());
    }

    public function test_per_pageが1から100の範囲であれば通過する(): void
    {
        $validator1 = Validator::make(['per_page' => 1], $this->rules);
        $validator100 = Validator::make(['per_page' => 100], $this->rules);

        $this->assertTrue($validator1->passes());
        $this->assertTrue($validator100->passes());
    }

    public function test_per_pageが100を超える場合は拒否される(): void
    {
        $validator = Validator::make(['per_page' => 101], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('per_page', $validator->errors()->toArray());
    }

    public function test_per_pageが0以下の場合は拒否される(): void
    {
        $validator = Validator::make(['per_page' => 0], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('per_page', $validator->errors()->toArray());
    }
}
