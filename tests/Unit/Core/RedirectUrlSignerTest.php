<?php

use App\Modules\Core\Support\RedirectUrlSigner;

describe('RedirectUrlSigner', function () {
    it('returns the sign URL with token appended', function () {
        $signer = new RedirectUrlSigner('test-key');
        $signed = $signer->append('https://example.test/download', 'https://example.test/login');

        expect($signed)->toStartWith('https://example.test/download?r=');
        expect(parse_url($signed, PHP_URL_QUERY))->toContain('r=');
    });

    it('uses & as separator when base URL has query params', function () {
        $signer = new RedirectUrlSigner('test-key');
        $signed = $signer->append('https://example.test/download?id=5', 'https://example.test/login');

        expect($signed)->toStartWith('https://example.test/download?id=5&r=');
    });

    it('verifies valid signed token and returns the URL', function () {
        $signer = new RedirectUrlSigner('test-key');
        $expectedUrl = 'https://example.test/admin/login';
        $token = $signer->sign($expectedUrl);

        expect($signer->verify($token))->toBe($expectedUrl);
    });

    it('verifies URL with multi-byte and special chars', function () {
        $signer = new RedirectUrlSigner('test-key');
        $expectedUrl = 'https://example.test/login?name=José Peña&role=reviewer';
        $token = $signer->sign($expectedUrl);

        expect($signer->verify($token))->toBe($expectedUrl);
    });

    it('returns null for empty token', function () {
        $signer = new RedirectUrlSigner('test-key');

        expect($signer->verify(''))->toBeNull();
        expect($signer->verify(null))->toBeNull();
    });

    it('returns null for malformed token (missing dot)', function () {
        $signer = new RedirectUrlSigner('test-key');

        expect($signer->verify('invalidtokenwithoutdot'))->toBeNull();
    });

    it('returns null when signature does not match', function () {
        $signer = new RedirectUrlSigner('test-key');
        $signed = $signer->sign('https://example.test/login');

        // Tamper with the signature
        $parts = explode('.', $signed);
        $tampered = $parts[0].'.0000000000000000000000000000000000000000000000000000000000000000';

        expect($signer->verify($tampered))->toBeNull();
    });

    it('returns null when key differs from the one that signed', function () {
        $signerA = new RedirectUrlSigner('key-A');
        $signerB = new RedirectUrlSigner('key-B');

        $token = $signerA->sign('https://example.test/login');

        expect($signerB->verify($token))->toBeNull();
    });

    it('rejects invalid base64 encoding', function () {
        $signer = new RedirectUrlSigner('test-key');

        // Invalid base64 payload with a valid-looking signature
        expect($signer->verify('!!!invalid-base64!!!.deadbeef'))->toBeNull();
    });

    it('exposes parameter name via parameter()', function () {
        expect((new RedirectUrlSigner('k'))->parameter())->toBe('r');
        expect((new RedirectUrlSigner('k', 'panel'))->parameter())->toBe('panel');
    });

    it('roundtrips append + verify with complex URLs', function () {
        $signer = new RedirectUrlSigner('test-key');
        $panelLoginUrl = 'https://example.test/super-admin/login';
        $signed = $signer->append('https://example.test/downloads/report-1?inline=1', $panelLoginUrl);

        // Parse the r= param
        $query = parse_url($signed, PHP_URL_QUERY);
        parse_str((string) $query, $params);

        expect($signer->verify($params['r'] ?? null))->toBe($panelLoginUrl);
    });
});
