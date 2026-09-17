<?php

namespace App\Application\Media;

use App\Domain\Media\MediaFile;
use App\Domain\Media\MediaFileRepositoryInterface;

class GetMediaFile
{
    public function __construct(
        private MediaFileRepositoryInterface $mediaFileRepository,
    ) {
    }

    public function execute(GetMediaFileRequest $request): MediaFile
    {
        $mediaFile = $this->mediaFileRepository->findById($request->mediaId);
        if (null === $mediaFile) {
            throw new MediaFileNotFoundException('Media file not found.');
        }

        return $mediaFile;
    }
}
