<?php
declare(strict_types=1);

namespace App\Auth;

/**
 * The refresh token lives in an HttpOnly cookie, never in localStorage.
 *
 * That is the whole point of splitting the tokens: a XSS payload can read the
 * short-lived access token from memory, but it cannot read or exfiltrate the
 * long-lived refresh token, so it cannot mint new sessions.
 *
 * `Path=/auth` keeps the cookie off every other endpoint, so the browser only
 * ships it to /auth/refresh and /auth/logout.
 */
final class RefreshTokenCookie {
    public const NAME = 'rex_refresh_token';
    private const PATH = '/auth';

    public static function read(): ?string {
        $value = $_COOKIE[self::NAME] ?? null;
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        return $value;
    }

    public static function send(string $token, int $expiresAt): void {
        setcookie(self::NAME, $token, self::options($expiresAt));
    }

    public static function clear(): void {
        // A past expiry is what tells the browser to drop it; the attributes
        // must match the ones used when setting or the delete is ignored.
        setcookie(self::NAME, '', self::options(time() - 3600));
    }

    /** @return array<string, mixed> */
    private static function options(int $expiresAt): array {
        $sameSite = self::sameSite();

        return [
            'expires' => $expiresAt,
            'path' => self::PATH,
            'secure' => self::isSecure($sameSite),
            'httponly' => true,
            'samesite' => $sameSite,
        ];
    }

    private static function sameSite(): string {
        $configured = ucfirst(strtolower(trim((string) getenv('REFRESH_COOKIE_SAMESITE'))));
        return in_array($configured, ['Lax', 'Strict', 'None'], true) ? $configured : 'Lax';
    }

    /**
     * SameSite=None is only honoured on secure cookies, so it forces the flag
     * on regardless of how the request arrived.
     */
    private static function isSecure(string $sameSite): bool {
        if ($sameSite === 'None') {
            return true;
        }
        if (filter_var(getenv('REFRESH_COOKIE_SECURE'), FILTER_VALIDATE_BOOL)) {
            return true;
        }
        return ($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off';
    }
}
