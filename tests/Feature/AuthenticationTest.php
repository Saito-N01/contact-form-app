<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済みユーザーは管理画面を表示できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(200);
        $response->assertViewIs('admin.index');
    }

    public function test_未認証ユーザーは管理画面にアクセスするとログインページへリダイレクトされる(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_未認証ユーザーはお問い合わせ詳細ページにもアクセスできない(): void
    {
        $response = $this->get('/admin/contacts/1');

        $response->assertRedirect('/login');
    }

    public function test_正しい情報でログインできる(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }

    public function test_メールアドレス未入力の場合はエラーになる(): void
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_パスワード未入力の場合はエラーになる(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com']);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_パスワードが間違っている場合はログインできない(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_存在しないメールアドレスではログインできない(): void
    {
        $response = $this->post('/login', [
            'email' => 'notfound@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_ログアウトできる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout');

        $this->assertGuest();
    }
}
