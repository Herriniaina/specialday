<?php

namespace App\Tests\Infrastructure\Media\Imagor;

use App\Infrastructure\Media\Imagor\ImagorUrlSigner;
use PHPUnit\Framework\TestCase;

class ImagorUrlSignerTest extends TestCase
{
    private function expectedSignature(string $path, string $secret): string
    {
        return strtr(base64_encode(hash_hmac('sha1', $path, $secret, true)), '+/', '-_');
    }

    public function testSignBuildsAUrlWithTheHmacSha1Signature(): void
    {
        $signer = new ImagorUrlSigner('https://example.test/imagor', 'top-secret');

        $url = $signer->sign('company/company-id/media/media-id');

        $expectedSignature = $this->expectedSignature('company/company-id/media/media-id', 'top-secret');
        self::assertSame('https://example.test/imagor/'.$expectedSignature.'/company/company-id/media/media-id', $url);
    }

    public function testSignIncludesOperationsBeforeThePathWhenGiven(): void
    {
        $signer = new ImagorUrlSigner('https://example.test/imagor', 'top-secret');

        $url = $signer->sign('company/company-id/media/media-id', 'fit-in/800x600');

        $expectedPath = 'fit-in/800x600/company/company-id/media/media-id';
        $expectedSignature = $this->expectedSignature($expectedPath, 'top-secret');
        self::assertSame('https://example.test/imagor/'.$expectedSignature.'/'.$expectedPath, $url);
    }

    public function testSignKeepsTheBase64PaddingToMatchImagorsGoUrlEncoding(): void
    {
        // imagor (Go) signs with base64.URLEncoding, which KEEPS "=" padding — unlike
        // RawURLEncoding. A SHA1 HMAC (20 bytes) always base64-encodes with exactly one "=".
        $signer = new ImagorUrlSigner('https://example.test/imagor', 'top-secret');

        $url = $signer->sign('company/company-id/media/media-id');

        self::assertStringContainsString('=', explode('/company/', $url)[0]);
    }

    public function testSignStripsATrailingSlashFromTheBaseUrl(): void
    {
        $signer = new ImagorUrlSigner('https://example.test/imagor/', 'top-secret');

        $url = $signer->sign('path');

        self::assertStringStartsWith('https://example.test/imagor/', $url);
        self::assertStringNotContainsString('imagor//', $url);
    }
}
