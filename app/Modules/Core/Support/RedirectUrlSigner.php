<?php

namespace App\Modules\Core\Support;

use Illuminate\Support\Facades\Config;

/**
 * Signs and verifies panel redirect URLs for guest authentication recovery.
 *
 * When an unauthenticated user clicks a download link (e.g., from an email),
 * we cannot know which panel they should be redirected to without extra context.
 * This signer encodes the intended panel URL with an HMAC signature so the
 * guest-redirect middleware can safely redirect them back after login — without
 * needing to enumerate allowed domains or query the database.
 *
 * Token format: "<base64(url)>.<hex(hmac-sha256(url, app.key))>"
 */
final readonly class RedirectUrlSigner
{
    private string $key;

    private string $parameter;

    public function __construct(?string $key = null, string $parameter = 'r')
    {
        $this->key = $key ?? (string) Config::get('app.key');
        $this->parameter = $parameter;
    }

    /**
     * Sign the given URL and return it as a token suitable for query parameter embedding.
     */
    public function sign(string $url): string
    {
        $encoded = rtrim(strtr(base64_encode($url), '+/', '-_'), '=');

        return $encoded.'.'.hash_hmac('sha256', $url, $this->key);
    }

    /**
     * Verify a token and return the original URL, or null when signature is invalid.
     */
    public function verify(?string $token): ?string
    {
        if ($token === null || $token === '') {
            return null;
        }

        $parts = explode('.', $token, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$encoded, $signature] = $parts;

        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);

        if ($decoded === false || $decoded === '') {
            return null;
        }

        $expected = hash_hmac('sha256', $decoded, $this->key);

        return hash_equals($expected, $signature) ? $decoded : null;
    }

    /**
     * Append a signed redirect token to a URL as query parameter.
     */
    public function append(string $url, string $panelUrl): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.$this->parameter.'='.urlencode($this->sign($panelUrl));
    }

    public function parameter(): string
    {
        return $this->parameter;
    }
}
