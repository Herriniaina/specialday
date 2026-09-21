<?php

namespace App\Infrastructure\Media\Imagor;

use App\Application\Media\ImageUrlSignerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ImagorUrlSigner implements ImageUrlSignerInterface
{
    public function __construct(
        #[Autowire('%env(DEFAULT_URI)%/imagor')]
        private string $baseUrl,
        #[Autowire('%env(IMAGOR_SECRET)%')]
        private string $secret,
    ) {
    }

    #[\Override]
    public function sign(string $path, string $operations = ''): string
    {
        $unsafePath = '' === $operations ? $path : rtrim($operations, '/').'/'.$path;
        // Must match Go's base64.URLEncoding (imagorpath.Sign): URL-safe alphabet, padding KEPT
        // (unlike RawURLEncoding). Confirmed against imagor's actual signer.go source.
        $signature = strtr(base64_encode(hash_hmac('sha1', $unsafePath, $this->secret, true)), '+/', '-_');

        return rtrim($this->baseUrl, '/').'/'.$signature.'/'.$unsafePath;
    }
}
