<?php

namespace App\Infrastructure\Media\Storage;

use App\Application\Media\PublicVideoUrlBuilderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class PublicVideoUrlBuilder implements PublicVideoUrlBuilderInterface
{
    public function __construct(
        #[Autowire('%env(PUBLIC_VIDEO_BASE_URL)%')]
        private string $publicBaseUrl,
    ) {
    }

    #[\Override]
    public function build(string $path): string
    {
        return rtrim($this->publicBaseUrl, '/').'/'.$path;
    }
}
