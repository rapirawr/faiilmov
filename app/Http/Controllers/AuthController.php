<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            Log::info('User login successful', [
                'user_id' => Auth::id(),
                'email' => Auth::user()->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            return redirect()->intended('/')->with('success', 'Selamat datang kembali!');
        }

        Log::warning('Failed login attempt', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->withErrors([
            'email' => 'Kombinasi email dan password tidak cocok.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        Log::info('New user registered', [
            'user_id' => $user->id,
            'email'   => $user->email,
            'ip'      => $request->ip(),
        ]);

        return redirect('/')
            ->with('success', 'Selamat datang di faiilmov! Akun Anda berhasil dibuat.');
    }

    public function logout(Request $request)
    {
        if ($request->session()->has('active_profile_id')) {
            return redirect()->back()->with('error', 'Gagal Keluar: Anda sedang menggunakan profil sub-akun. Silakan beralih ke Akun Utama terlebih dahulu untuk dapat Keluar (Log Out).');
        }

        $userId = Auth::id();
        $email = Auth::user()?->email;
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        Log::info('User logout', [
            'user_id' => $userId,
            'email'   => $email,
            'ip'      => $request->ip(),
        ]);

        return redirect('/')->with('success', 'Berhasil keluar.');
    }

    // ─────────────────────────────────────────────────────────────
    // PASSWORD RESET
    // ─────────────────────────────────────────────────────────────

    /**
     * Show the forgot-password form.
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send the password reset link to the given email.
     * Rate-limited via route: throttle:6,1
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            Log::info('Password reset link sent', ['email' => $request->email, 'ip' => $request->ip()]);

            return back()->with('status', 'Link reset password telah dikirim ke email Anda. Periksa folder spam jika tidak muncul dalam beberapa menit.');
        }

        // Intentionally vague response to prevent user enumeration
        return back()->with('status', 'Jika email tersebut terdaftar, kami akan segera mengirimkan link reset password.');
    }

    /**
     * Show the reset-password form for the given token.
     */
    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Validate the token and update the user's password.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'                 => 'required',
            'email'                 => 'required|email',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                Log::info('Password reset successful', [
                    'user_id' => $user->id,
                    'email'   => $user->email,
                ]);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('success', 'Password berhasil direset. Silakan masuk dengan password baru Anda.');
        }

    }
}
