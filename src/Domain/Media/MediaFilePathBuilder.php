<?php

namespace App\Domain\Media;

final class MediaFilePathBuilder
{
    public static function build(string $companyId, string $mediaId): string
    {
        return \sprintf('company/%s/media/%s', $companyId, $mediaId);
    }
}
