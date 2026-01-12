<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Show the login form
     */
    public function index()
    {
        // Jika sudah login, redirect ke admin
        if (Auth::check()) {
            return redirect('/admin');
        }

        return view('auth.login');
    }

    /**
     * Handle authentication request
     */
    public function authenticate(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = $request->input('login');
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        // Cek apakah login adalah email atau NIK
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'nik';

        // Cari user berdasarkan email atau NIK
        $user = User::where($fieldType, $login)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'login' => ['Email atau NIK tidak ditemukan.'],
            ]);
        }

        // Attempt login
        $credentials = [
            $fieldType => $login,
            'password' => $password,
        ];

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->intended('/admin');
        }

        throw ValidationException::withMessages([
            'login' => ['Email/NIK atau password salah.'],
        ]);
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

