<?php

namespace App\Infrastructure\Media\Http;

use App\Application\Media\FileStorageInterface;
use App\Application\Media\GetMediaFile;
use App\Application\Media\GetMediaFileRequest;
use App\Application\Media\MediaFileNotFoundException;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class GetMediaFileController extends ApiAbstractController
{
    public function __construct(
        private GetMediaFile $getMediaFile,
        private FileStorageInterface $fileStorage,
    ) {
    }

    #[Route('/api/public/media/{mediaId}', name: 'app_public_media_get', methods: ['GET'])]
    public function __invoke(string $mediaId): Response
    {
        try {
            $mediaFile = $this->getMediaFile->execute(new GetMediaFileRequest($mediaId));
            $stream = $this->fileStorage->readStream($mediaFile->path);
        } catch (MediaFileNotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (\Exception) {
            return $this->error();
        }

        $response = new StreamedResponse(static function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        });

        $response->headers->set('Content-Type', $mediaFile->fileType);
        $response->headers->set('Content-Length', (string) $mediaFile->fileSize);
        $response->headers->set(
            'Content-Disposition',
            HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $mediaFile->filename),
        );

        return $response;
    }
}
