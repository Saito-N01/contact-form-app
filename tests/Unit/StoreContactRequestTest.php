<?php

namespace Tests\Unit;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreContactRequestTest extends TestCase
{
    use RefreshDatabase;

    private array $rules;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rules = (new StoreContactRequest)->rules();
    }

    /**
     * 有効なデータ一式を返すヘルパー（カテゴリ・タグはFactoryでDBに実在させる）
     */
    private function validData(array $overrides = []): array
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        return array_merge([
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区千駄ヶ谷1-2-3',
            'building' => 'サンプルマンション101',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容のテストです。',
            'tag_ids' => $tags->pluck('id')->toArray(),
        ], $overrides);
    }

    public function test_全ての必須項目とタグ入力を満たしていれば通過する(): void
    {
        $validator = Validator::make($this->validData(), $this->rules);

        $this->assertTrue($validator->passes());
    }

    public function test_タグ未選択でも通過する(): void
    {
        $data = $this->validData(['tag_ids' => null]);

        $validator = Validator::make($data, $this->rules);

        $this->assertTrue($validator->passes());
    }

    /**
     * @dataProvider requiredFieldProvider
     */
    public function test_必須項目が未入力の場合は失敗する(string $field): void
    {
        $data = $this->validData([$field => '']);

        $validator = Validator::make($data, $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey($field, $validator->errors()->toArray());
    }

    public static function requiredFieldProvider(): array
    {
        return [
            'first_name' => ['first_name'],
            'last_name' => ['last_name'],
            'gender' => ['gender'],
            'email' => ['email'],
            'tel' => ['tel'],
            'address' => ['address'],
            'category_id' => ['category_id'],
            'detail' => ['detail'],
        ];
    }

    public function test_建物名は空でも通過する(): void
    {
        $data = $this->validData(['building' => '']);

        $validator = Validator::make($data, $this->rules);

        $this->assertTrue($validator->passes());
    }

    /**
     * @dataProvider invalidTelProvider
     */
    public function test_不正な電話番号形式は拒否される(string $tel): void
    {
        $data = $this->validData(['tel' => $tel]);

        $validator = Validator::make($data, $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('tel', $validator->errors()->toArray());
    }

    public static function invalidTelProvider(): array
    {
        return [
            'ハイフンあり' => ['090-1234-5678'],
            '9桁(短すぎ)' => ['123456789'],
            '12桁(長すぎ)' => ['123456789012'],
            '英数字混在' => ['090abcd5678'],
            '全角数字' => ['０９０１２３４５６７８'],
        ];
    }

    public function test_電話番号10桁と11桁はどちらも通過する(): void
    {
        $data10 = $this->validData(['tel' => '0312345678']);
        $data11 = $this->validData(['tel' => '09012345678']);

        $this->assertTrue(Validator::make($data10, $this->rules)->passes());
        $this->assertTrue(Validator::make($data11, $this->rules)->passes());
    }

    public function test_不正なメール形式は拒否される(): void
    {
        $data = $this->validData(['email' => 'not-an-email']);

        $validator = Validator::make($data, $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_不正な性別の値は拒否される(): void
    {
        $data = $this->validData(['gender' => 4]);

        $validator = Validator::make($data, $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gender', $validator->errors()->toArray());
    }

    public function test_お問い合わせ内容が120文字を超えると拒否される(): void
    {
        $data = $this->validData(['detail' => str_repeat('あ', 121)]);

        $validator = Validator::make($data, $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('detail', $validator->errors()->toArray());
    }

    public function test_お問い合わせ内容がちょうど120文字なら通過する(): void
    {
        $data = $this->validData(['detail' => str_repeat('あ', 120)]);

        $validator = Validator::make($data, $this->rules);

        $this->assertTrue($validator->passes());
    }

    public function test_存在しないカテゴリ_i_dは拒否される(): void
    {
        $data = $this->validData(['category_id' => 9999]);

        $validator = Validator::make($data, $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
    }

    public function test_存在しないタグ_i_dが含まれると拒否される(): void
    {
        $data = $this->validData(['tag_ids' => [9999]]);

        $validator = Validator::make($data, $this->rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('tag_ids.0', $validator->errors()->toArray());
    }
}
