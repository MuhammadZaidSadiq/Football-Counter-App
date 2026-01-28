<?php

namespace App\Http\Controllers;

use App\Models\SiteUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticationController extends Controller
{
    public function showAccessForm(Request $request)
    {
        $action = $request->query('action', 'login');
        return view('authentication.access-form', compact('action'));
    }

    public function processLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = SiteUser::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Invalid username or password.'],
            ]);
        }

        Auth::guard('site')->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('control-panel.overview'));
    }

    public function processRegistration(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:50|unique:site_users',
            'email' => 'required|email|max:100|unique:site_users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = SiteUser::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::guard('site')->login($user);
        $request->session()->regenerate();

        return redirect()->route('control-panel.overview');
    }

    public function processLogout(Request $request)
    {
        Auth::guard('site')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('homepage.welcome');
    }
}