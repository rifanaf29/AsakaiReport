# SSO Login Mechanism

## Purpose
Document how the existing SSO login flow works so it can be recreated in other applications.

## Composer packages involved
- `firebase/php-jwt` (used): decodes and validates the incoming JWT signature via HS256 using `JWT_SECRET`.
- `php-open-source-saver/jwt-auth` (present, but not used in this flow): shipped with the project but not referenced by the SSO controller; safe to omit when porting the mechanism unless other features depend on it.

## Components
- Route definition: `/sso/login` GET in [routes/web.php](../routes/web.php#L37-L73).
- Controller: `SSOController@login` in [app/Http/Controllers/SSOController.php](../app/Http/Controllers/SSOController.php#L10-L39).
- Service: `JwtService::decodeToken` in [app/Services/JwtService.php](../app/Services/JwtService.php#L8-L13).

## Environment/config
- `JWT_SECRET` must match the signing secret used by the identity provider. The token is assumed to be HS256-signed.
- No issuer/audience checks are performed; only the signature (and any standard claims that `firebase/php-jwt` validates, like `exp` if present) are validated.

## Expected JWT payload
The controller expects at least one of these claims in the decoded token body:
- `email` – primary identifier used to find the local user.
- `name` – fallback identifier when `email` is absent or unknown locally; also populates the local user name when the account is created.

At least one of the two must be present. Other claims are ignored.

## User matching order
1. **By email** – exact match on `users.email` when the claim is present.
2. **By name** – exact match on `users.name` when step 1 finds nothing. `users.email` is nullable so accounts can be registered with a name only.
   - If the name matches **more than one** user the login is refused: the account is ambiguous and picking one could hand over the wrong account.
   - If a single user matches and that user has no email while the token carries one, the email is written to the account so later logins take the faster email path.
3. **Create** – no match at all creates a user from the claims with a random password (sign in through SSO only).

## Request flow (happy path)
1. Identity provider issues a JWT signed with HS256 and the shared `JWT_SECRET` containing `email` and `name` claims.
2. User is redirected to `/sso/login?token=<jwt>`.
3. `SSOController@login` extracts `token` from the query string.
4. `JwtService::decodeToken` calls `JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'))` to verify and decode.
5. The app resolves the local user following the matching order above, creating one if nothing matches.
6. `Auth::login($user)` logs the user into Laravel, then redirects to `/dashboard`.

## Error handling
- Missing token ⇒ redirect `/login` with error message "SSO token missing".
- Decode/validation failure ⇒ redirect `/login` with error message "Invalid or expired SSO token".
- Payload without both `email` and `name` ⇒ redirect `/login` with error message "Invalid or expired SSO token".
- Name matching more than one local user ⇒ redirect `/login` with error message "More than one account uses this name. Please contact the administrator."

## Security considerations
- HS256 symmetric secret must be kept private and match across systems.
- No `iss`, `aud`, or nonce validation is implemented—add if you need stronger guarantees or multi-tenant separation.
- The route is unauthenticated; ensure tokens are short-lived and delivered over HTTPS only.

## How to recreate in another Laravel app
1. Install `firebase/php-jwt` and set `JWT_SECRET` in `.env` to the shared secret.
2. Add a service equivalent to `JwtService` that decodes HS256 tokens with that secret.
3. Create an `SSOController@login` that:
   - Reads `token` from the query string.
   - Decodes/validates it via the service.
   - Maps `email`/`name` to a local user record (create if missing) and logs the user in.
   - Redirects to your desired post-login URL.
4. Register a GET route like `/sso/login` pointing to the controller method.
5. From the identity provider, redirect users to `/sso/login?token=<jwt>` with the agreed claims.

## Example token generation (HS256)
```php
use Firebase\JWT\JWT;
$payload = [
    'email' => 'jane.doe@example.com',
    'name'  => 'Jane Doe',
    'exp'   => time() + 300,
];
$token = JWT::encode($payload, getenv('JWT_SECRET'), 'HS256');
```

## Notes for maintainers
- If you switch to an asymmetric flow (RS256/ES256), update `JwtService` to use the public key and adjust `Key` accordingly.
- If you want to enforce claim checks, add issuer/audience validation inside `SSOController@login` after decoding.
