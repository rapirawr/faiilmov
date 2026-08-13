<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    /**
     * Supported OAuth providers.
     */
    private const ALLOWED_PROVIDERS = ['google'];

    /**
     * Redirect the user to the OAuth provider's login page.
     */
    public function redirect(string $provider)
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the OAuth callback from the provider.
     *
     * Collision policy: if the email already exists (registered manually),
     * auto-link the provider to the existing account (safe because the
     * OAuth provider has already verified ownership of that email).
     */
    public function callback(Request $request, string $provider)
    {
        $this->validateProvider($provider);

        try {
            /** @var \Laravel\Socialite\Two\User $socialUser */
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            Log::warning("Social login callback failed [{$provider}]", [
                'error' => $e->getMessage(),
                'ip'    => $request->ip(),
            ]);

            return redirect()->route('login')
                ->withErrors(['email' => 'Login dengan ' . ucfirst($provider) . ' gagal. Silakan coba lagi.']);
        }

        try {
            // Look up by provider + provider_id first (fastest path — returning social user)
            $user = User::where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->first();

            if (!$user) {
                // Check if email is already registered (manual account)
                $user = User::where('email', $socialUser->getEmail())->first();

                if ($user) {
                    // Auto-link: attach provider to existing account
                    $user->update([
                        'provider'           => $provider,
                        'provider_id'        => $socialUser->getId(),
                        // Mark email as verified since OAuth provider vouches for it
                        'email_verified_at'  => $user->email_verified_at ?? now(),
                    ]);

                    Log::info("Social login: linked [{$provider}] to existing account", [
                        'user_id'     => $user->id,
                        'email'       => $user->email,
                        'provider_id' => $socialUser->getId(),
                    ]);
                } else {
                    // New user — create account with random secure password (since DB column is NOT NULL)
                    $user = User::create([
                        'name'              => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                        'email'             => $socialUser->getEmail(),
                        'password'          => Hash::make(Str::random(32)),
                        'provider'          => $provider,
                        'provider_id'       => $socialUser->getId(),
                        'avatar'            => $socialUser->getAvatar(),
                        // Email is verified by OAuth provider
                        'email_verified_at' => now(),
                    ]);

                    Log::info("Social login: new user created via [{$provider}]", [
                        'user_id' => $user->id,
                        'email'   => $user->email,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("Social login user creation/linking failed [{$provider}]", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')
                ->withErrors(['email' => 'Terjadi kesalahan saat memproses akun. Silakan coba lagi.']);
        }

        // Check if account is banned
        if ($user->isBanned()) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Akun Anda telah diblokir. Hubungi support untuk informasi lebih lanjut.']);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended('/')->with('success', 'Selamat datang, ' . $user->name . '!');
    }

    /**
     * Validate that the given provider is supported.
     */
    private function validateProvider(string $provider): void
    {
        if (!in_array($provider, self::ALLOWED_PROVIDERS, strict: true)) {
            abort(404, 'Provider OAuth tidak didukung.');
        }
    }
}
