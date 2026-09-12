<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_有効な情報で登録できる(): void
    {
        $response = $this->post('/register', [
            'name' => 'テスト管理者',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'テスト管理者',
            'email' => 'admin@example.com',
        ]);
        $response->assertRedirect('/admin');
    }

    public function test_パスワードはハッシュ化されて保存される(): void
    {
        $this->post('/register', [
            'name' => 'テスト管理者',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'admin@example.com')->firstOrFail();

        $this->assertNotEquals('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_登録済みのメールアドレスでは登録できない(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $response = $this->post('/register', [
            'name' => 'テスト管理者2',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_パスワード確認が一致しない場合は登録できない(): void
    {
        $response = $this->post('/register', [
            'name' => 'テスト管理者',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
    }

    public function test_必須項目が未入力の場合は登録できない(): void
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
    }
}
