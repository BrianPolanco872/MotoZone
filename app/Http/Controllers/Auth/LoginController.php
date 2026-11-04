<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route($this->routeForUser(Auth::user()));
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $user = $request->user();

        if (! $this->nameMatches($user, $request->string('name')->toString())) {
            $this->logoutCurrentSession($request);

            throw ValidationException::withMessages([
                'name' => 'El nombre no coincide con la cuenta.',
            ]);
        }

        if (! $user->is_active) {
            $this->logoutCurrentSession($request);

            throw ValidationException::withMessages([
                'email' => 'Tu cuenta está inactiva. Contacta al administrador.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route($this->routeForUser($user)));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->logoutCurrentSession($request);

        return redirect()->route('login');
    }

    private function logoutCurrentSession(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * Compare the typed name against the account, ignoring casing and extra whitespace.
     */
    private function nameMatches(?User $user, string $name): bool
    {
        if (! $user) {
            return false;
        }

        return (string) Str::of($user->name)->squish()->lower() === (string) Str::of($name)->squish()->lower();
    }

    private function routeForUser(?User $user): string
    {
        if ($user?->hasRole('admin')) {
            return 'admin.dashboard';
        }

        if ($user?->can('pos.access')) {
            return 'pos.index';
        }

        return $user?->can('admin.products.manage') ? 'admin.products.index' : 'home';
    }
}
