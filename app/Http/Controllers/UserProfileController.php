<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        abort_unless($request->user()->exists, 403);

        $request->user()->update($request->validated());

        return back()->with('status', 'profile-updated');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        abort_unless($request->user()->exists, 403);

        $request->user()->update([
            'password' => $request->validated('password'),
        ]);

        return back()->with('status', 'password-updated');
    }
}
