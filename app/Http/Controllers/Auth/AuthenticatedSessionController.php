<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\UserRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();
        $fallback = $user->isStaff()
            ? route('admin.dashboard')
            : route('wallet.dashboard');

        $intended = $request->session()->get('url.intended');
        if (is_string($intended) && ! $this->userMayFollowIntendedUrl($user, $intended)) {
            $request->session()->forget('url.intended');
        }

        return redirect()->intended($fallback);
    }

    private function userMayFollowIntendedUrl(User $user, string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return true;
        }

        $path = '/'.ltrim($path, '/');

        if (str_starts_with($path, '/admin/staff')) {
            return $user->role === UserRoles::SUPER_ADMIN;
        }

        if (str_starts_with($path, '/admin')) {
            return $user->isStaff();
        }

        return true;
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
