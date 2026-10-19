<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Support\Phone;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

/** SPA cookie auth on Sanctum (BE-19 … BE-23). */
class AuthController extends Controller
{
    public function __construct(private CartService $carts) {}

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user() ? new UserResource($request->user()) : null]);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', Phone::rule()],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $guestCart = $this->carts->guestCart($request);
        $user = User::create(['name' => $data['name'], 'email' => strtolower($data['email']), 'phone' => Phone::normalize($data['phone']), 'password' => $data['password']]);
        event(new Registered($user));

        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();
        $this->carts->mergeIntoUser($guestCart, $user);

        return response()->json(['data' => new UserResource($user)], 201)->withCookie($this->carts->forgetCookie());
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string'], 'remember' => ['boolean']]);

        if (! Auth::guard('web')->attempt(['email' => strtolower($data['email']), 'password' => $data['password']], $data['remember'] ?? true)) {
            throw ValidationException::withMessages(['email' => [__('auth.failed')]]);
        }
        $guestCart = $this->carts->guestCart($request);
        $request->session()->regenerate();
        $this->carts->mergeIntoUser($guestCart, $request->user());

        return response()->json(['data' => new UserResource($request->user())])->withCookie($this->carts->forgetCookie());
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(null, 204);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink(['email' => strtolower($request->input('email'))]);

        // same answer whether or not the email exists (no account enumeration)
        return response()->json(['message' => __('passwords.user')]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);
        $status = Password::reset(['email' => strtolower($data['email'])] + $data, function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return response()->json(['message' => __($status)]);
    }

    public function verifyEmail(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);
        abort_unless(hash_equals($hash, sha1($user->getEmailForVerification())), 403);
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return response()->json(['message' => 'تم تأكيد بريدك الإلكتروني.']);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return response()->json(['message' => 'أرسلنا رابط التأكيد إلى بريدك.']);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'phone' => ['sometimes', 'string', Phone::rule()],
            'current_password' => ['required_with:password', 'current_password'],
            'password' => ['sometimes', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);
        if (isset($data['phone'])) {
            $data['phone'] = Phone::normalize($data['phone']);
        }
        unset($data['current_password']);
        $user->update($data);

        return response()->json(['data' => new UserResource($user)]);
    }
}
