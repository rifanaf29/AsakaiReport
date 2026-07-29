<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SSOController extends Controller
{
    public function __construct(private JwtService $jwtService)
    {
    }

    public function login(Request $request): RedirectResponse
    {
        $token = $request->query('token');

        // Keep token logging minimal to avoid leaking the full secret
        $maskedToken = $token ? substr($token, 0, 12) . '...' : null;

        if (!$token) {
            Log::warning('SSO login failed: missing token', [
                'route' => 'sso.login',
                'ip' => $request->ip(),
            ]);
            return redirect()->route('login')->with('error', 'SSO token missing');
        }

        try {
            $payload = $this->jwtService->decodeToken($token);
        } catch (\Throwable $exception) {
            Log::warning('SSO login failed: token decode error', [
                'route' => 'sso.login',
                'ip' => $request->ip(),
                'token_prefix' => $maskedToken,
                'error' => $exception->getMessage(),
            ]);
            return redirect()->route('login')->with('error', 'Invalid or expired SSO token');
        }

        $email = trim((string) ($payload['email'] ?? '')) ?: null;
        $name = trim((string) ($payload['name'] ?? '')) ?: null;

        if (!$email && !$name) {
            Log::warning('SSO login failed: payload missing both email and name', [
                'route' => 'sso.login',
                'ip' => $request->ip(),
                'token_prefix' => $maskedToken,
                'payload' => $payload,
            ]);
            return redirect()->route('login')->with('error', 'Invalid or expired SSO token');
        }

        // Email is the reliable identifier; fall back to name for accounts registered
        // without one. Names are not unique, so an ambiguous match must not log anyone in.
        $matchedBy = 'email';
        $user = $email ? User::where('email', $email)->first() : null;

        if (!$user && $name) {
            $byName = User::where('name', $name)->get();

            if ($byName->count() > 1) {
                Log::warning('SSO login failed: name matches multiple users', [
                    'route' => 'sso.login',
                    'ip' => $request->ip(),
                    'token_prefix' => $maskedToken,
                    'name' => $name,
                    'user_ids' => $byName->pluck('id')->all(),
                ]);
                return redirect()->route('login')
                    ->with('error', 'More than one account uses this name. Please contact the administrator.');
            }

            $user = $byName->first();

            if ($user) {
                $matchedBy = 'name';

                // Attach the email so the next login matches on it instead of the name.
                if ($email && !$user->email && !User::where('email', $email)->exists()) {
                    $user->forceFill(['email' => $email])->save();
                }
            }
        }

        if (!$user) {
            $matchedBy = 'created';
            $user = User::create([
                'name' => $name ?: $email,
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
            ]);
        }

        Auth::login($user);

        Log::info('SSO login succeeded', [
            'route' => 'sso.login',
            'ip' => $request->ip(),
            'token_prefix' => $maskedToken,
            'user_id' => $user->id,
            'email' => $email,
            'name' => $name,
            'matched_by' => $matchedBy,
        ]);

        return redirect('/dashboard');
    }
}
