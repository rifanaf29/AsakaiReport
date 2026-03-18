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

        $email = $payload['email'] ?? null;
        $name = $payload['name'] ?? null;

        if (!$email) {
            Log::warning('SSO login failed: payload missing email', [
                'route' => 'sso.login',
                'ip' => $request->ip(),
                'token_prefix' => $maskedToken,
                'payload' => $payload,
            ]);
            return redirect()->route('login')->with('error', 'Invalid or expired SSO token');
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name ?: $email,
                'password' => Hash::make(Str::random(40)),
            ]
        );

        Auth::login($user);

        Log::info('SSO login succeeded', [
            'route' => 'sso.login',
            'ip' => $request->ip(),
            'token_prefix' => $maskedToken,
            'user_id' => $user->id,
            'email' => $email,
        ]);

        return redirect('/dashboard');
    }
}
