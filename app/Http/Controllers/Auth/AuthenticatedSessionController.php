<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $identifier = trim($request->string('identifier')->toString());
        $column = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $identifier = $column === 'email'
            ? Str::lower($identifier)
            : User::normalizePhone($identifier);

        if (! Auth::attempt([
            $column => $identifier,
            'password' => $request->validated('password'),
        ], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'identifier' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
