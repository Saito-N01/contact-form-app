<?php

namespace Tests\Unit;

use App\Http\Requests\IndexContactRequest;
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

    public function test_keywordのみ指定した場合に通過する(): void
    {
        $validator = Validator::make(['keyword' => '山田太郎'], $this->rules);

        $this->assertTrue($validator->passes());
    }

    public function test_keywordが255文字を超えると拒否される(): void
    {
        $data = ['keyword' => str_repeat('あ', 256)];

        $validator = Validator::make($data, $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('keyword', $validator->errors()->toArray());
    }

    public function test_keywordがちょうど255文字なら通過する(): void
    {
        $data = ['keyword' => str_repeat('あ', 255)];

        $validator = Validator::make($data, $this->rules);

        $this->assertTrue($validator->passes());
    }

    /**
     * @dataProvider validGenderProvider
     */
    public function test_genderが0から3の場合は通過する(int $gender): void
    {
        $validator = Validator::make(['gender' => $gender], $this->rules);

        $this->assertTrue($validator->passes());
    }

    public static function validGenderProvider(): array
    {
        return [
            '0(全件)' => [0],
            '1(男性)' => [1],
            '2(女性)' => [2],
            '3(その他)' => [3],
        ];
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
            'マイナス値' => [-1],
            '文字列' => ['male'],
        ];
    }

    public function test_実在するcategory_idを指定した場合に通過する(): void
    {
        $category = Category::factory()->create();

        $validator = Validator::make(['category_id' => $category->id], $this->rules);

        $this->assertTrue($validator->passes());
    }

    /** 存在しないcategory_idを指定した場合は拒否される */
    public function test_存在しないcategory_idを指定した場合は拒否される(): void
    {
        $validator = Validator::make(['category_id' => 9999], $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
    }

    /** category_idが整数以外の場合は拒否される */
    public function test_category_idが整数以外の場合は拒否される(): void
    {
        $validator = Validator::make(['category_id' => 'abc'], $this->rules);

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
}
