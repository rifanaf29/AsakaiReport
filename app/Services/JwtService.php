<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    public function decodeToken(string $token): array
    {
        $secret = config('app.jwt_secret', env('JWT_SECRET'));

        // Allow slight clock skew between issuer and this server
        JWT::$leeway = config('app.jwt_leeway', env('JWT_LEEWAY', 60));

        $decoded = JWT::decode($token, new Key($secret, 'HS256'));

        return (array) $decoded;
    }
}
