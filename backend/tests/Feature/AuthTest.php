<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function spa()
    {
        return $this->withCredentials()->withHeader('Origin', 'http://localhost:5391');
    }

    public function test_register_normalizes_phone_and_ignores_role(): void
    {
        $this->spa()->postJson('/api/auth/register', [
            'name' => 'عمر', 'email' => 'Omar@Example.com', 'phone' => '+20 112 345 6789',
            'password' => 'midad2026', 'password_confirmation' => 'midad2026', 'role' => 'admin',
        ])->assertCreated()->assertJsonPath('data.phone', '01123456789')->assertJsonPath('data.role', 'customer');

        $this->assertAuthenticated();
        $this->assertSame('omar@example.com', User::latest('id')->first()->email);
    }

    public function test_login_failure_is_in_arabic_and_throttled(): void
    {
        $user = User::factory()->create(['password' => 'midad2026']);
        for ($i = 0; $i < 5; $i++) {
            $this->spa()->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'wrong'])
                ->assertStatus(422)->assertJsonPath('errors.email.0', 'البريد الإلكتروني أو كلمة المرور غير صحيحة.');
        }
        $this->spa()->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'midad2026'])->assertStatus(429);
    }

    public function test_guest_cart_merges_into_the_account_on_login(): void
    {
        $user = User::factory()->create(['password' => 'midad2026']);
        $mine = Cart::create(['token' => 'u', 'user_id' => $user->id]);
        $mine->items()->create(['kind' => 'product', 'product_variant_id' => 3, 'quantity' => 1]);
        $guest = Cart::create(['token' => 'guest-token']);
        $guest->items()->create(['kind' => 'product', 'product_variant_id' => 3, 'quantity' => 2]);
        $guest->items()->create(['kind' => 'product', 'product_variant_id' => 7, 'quantity' => 1]);

        $this->spa()->withCookie('midad_cart', 'guest-token')
            ->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'midad2026'])->assertOk();

        $this->assertDatabaseMissing('carts', ['id' => $guest->id]);
        $this->assertSame(3, $mine->items()->where('product_variant_id', 3)->value('quantity'));
        $this->assertSame(2, $mine->items()->count());
    }

    public function test_password_reset_does_not_reveal_accounts(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $known = $this->spa()->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertOk()->json('message');
        $unknown = $this->spa()->postJson('/api/auth/forgot-password', ['email' => 'nobody@example.com'])->assertOk()->json('message');
        $this->assertSame($known, $unknown);
        Notification::assertSentTo($user, ResetPassword::class, function ($n) use ($user) {
            $this->spa()->postJson('/api/auth/reset-password', [
                'token' => $n->token, 'email' => $user->email, 'password' => 'newpass2026', 'password_confirmation' => 'newpass2026',
            ])->assertOk();

            return true;
        });
    }

    public function test_account_routes_need_login_and_addresses_are_private(): void
    {
        $this->getJson('/api/account/orders')->assertUnauthorized()->assertJsonPath('message', 'سجّل الدخول أولاً.');
        [$a, $b] = User::factory()->count(2)->create();
        $addr = $this->actingAs($a)->postJson('/api/account/addresses', [
            'governorate_id' => 2, 'recipient_name' => 'أ', 'phone' => '01012345678', 'city' => 'الدقي', 'street' => 'التحرير',
        ])->assertCreated()->assertJsonPath('data.is_default', true)->json('data.id');
        $this->actingAs($b)->deleteJson("/api/account/addresses/{$addr}")->assertNotFound();
    }
}
