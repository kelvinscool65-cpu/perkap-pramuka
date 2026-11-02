<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /** Percobaan masuk yang salah sebelum dikunci sementara (per kombinasi akun + IP). */
    private const BATAS_PERCOBAAN = 5;

    public function create()
    {
        return view('auth.login');
    }

    /**
     * Login memakai SATU input "username/email". Field dideteksi otomatis: bila formatnya email
     * dicocokkan ke kolom email, bila bukan ke username.
     *
     * Akun yang DIBLOKIR hanya diberi tahu alasannya bila password-nya benar; orang lain yang
     * sekadar menebak username tetap melihat pesan generik, jadi status akun tidak bocor.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Username atau email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $kunciBatas = Str::transliterate(Str::lower($credentials['login']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($kunciBatas, self::BATAS_PERCOBAAN)) {
            event(new Lockout($request));
            $detik = RateLimiter::availableIn($kunciBatas);

            throw ValidationException::withMessages([
                'login' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$detik} detik.",
            ]);
        }

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $login = $field === 'email' ? Str::lower(trim($credentials['login'])) : trim($credentials['login']);

        $user = User::where($field, $login)->first(); // akun yang sudah dihapus otomatis tidak ditemukan

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($kunciBatas, 60);

            throw ValidationException::withMessages([
                'login' => 'Username/email atau password salah.',
            ]);
        }

        if ($user->isDiblokir()) {
            throw ValidationException::withMessages(['login' => $user->pesanBlokir()]);
        }

        RateLimiter::clear($kunciBatas);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // Redirect setelah login sesuai role (checklist brief)
        return redirect()->intended(
            $user->isAdmin() ? route('admin.dashboard') : route('user.dashboard')
        );
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
