<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;
use App\Models\Patient;
use App\Models\Therapist;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
            'role'     => 'required|in:admin,patient,therapist',
        ]);

        $role  = $request->role;
        $email = $request->email;
        $pass  = $request->password;

        $model = match($role) {
            'admin'     => Admin::class,
            'patient'   => Patient::class,
            'therapist' => Therapist::class,
        };

        $user = $model::where('email', $email)->first();

        if (!$user || !Hash::check($pass, $user->password)) {
            return back()->withErrors(['email' => 'Invalid credentials.']);
        }

        session([
            'auth_user' => [
                'role' => $role,
                'id'   => $user->getKey(),
                'name' => $user->first_name ?? $user->name,
            ]
        ]);

        return redirect()->route($role . '.dashboard');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'first_name'    => 'required|string|max:50',
            'last_name'     => 'required|string|max:50',
            'email'         => 'required|email|unique:patient,email',
            'phone'         => 'nullable|string|max:20',
            'password'      => 'required|confirmed|min:6',
            'date_of_birth' => 'nullable|date',
        ]);

        Patient::create([
            'first_name'    => $request->first_name,
            'last_name'     => $request->last_name,
            'email'         => $request->email,
            'phone'         => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'password'      => Hash::make($request->password),
        ]);

        return redirect()->route('login')->with('success', 'Registered successfully! Please login.');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('auth_user');
        return redirect()->route('login')->with('success', 'Logged out successfully.');
    }
}