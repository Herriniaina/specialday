<?php

namespace App\Application\Media;

use App\Application\Shared\TransactionInterface;
use App\Domain\Media\MediaFile;
use App\Domain\Media\MediaFilePathBuilder;
use App\Domain\Media\MediaFileRepositoryInterface;
use App\Domain\Media\MediaStatusEnum;
use App\Domain\Subscription\SubscriptionRepositoryInterface;

class TranscodeVideo
{
    public function __construct(
        private MediaFileRepositoryInterface $mediaFileRepository,
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private MediaSourceDownloaderInterface $mediaSourceDownloader,
        private PublicVideoStorageInterface $publicVideoStorage,
        private VideoTranscoderInterface $videoTranscoder,
        private FileStorageInterface $fileStorage,
        private TransactionInterface $transaction,
    ) {
    }

    public function execute(TranscodeVideoRequest $request): void
    {
        $mediaFile = $this->mediaFileRepository->findById($request->mediaFileId);
        if (null === $mediaFile || MediaStatusEnum::PROCESSING !== $mediaFile->status) {
            return;
        }

        $originalPath = $mediaFile->path;
        $localSource = $this->mediaSourceDownloader->downloadToLocalFile($originalPath);
        $localOutput = $localSource.'.out.mp4';

        try {
            $result = $this->videoTranscoder->transcode($localSource, $localOutput);
            $minutesUsed = (int) ceil($result->durationSeconds / 60);

            $subscription = $this->subscriptionRepository->findByCompanyId($mediaFile->companyId);
            if (null === $subscription || !$subscription->hasEncodingMinutesFor($minutesUsed, new \DateTimeImmutable())) {
                $this->mediaFileRepository->save($this->withStatus($mediaFile, MediaStatusEnum::FAILED));

                return;
            }

            $publicPath = MediaFilePathBuilder::build($mediaFile->companyId, (string) $mediaFile->getId());
            $this->publicVideoStorage->upload($localOutput, $publicPath);

            $readyMediaFile = $this->withStatus($mediaFile, MediaStatusEnum::READY, path: $publicPath, durationSeconds: $result->durationSeconds);

            $this->transaction->transactional(function () use ($readyMediaFile, $mediaFile, $minutesUsed): void {
                $this->mediaFileRepository->save($readyMediaFile);
                $this->subscriptionRepository->incrementEncodedMinutesUsed($mediaFile->companyId, $minutesUsed, new \DateTimeImmutable());
            });

            $this->fileStorage->delete($originalPath);
        } catch (\Throwable $e) {
            $this->mediaFileRepository->save($this->withStatus($mediaFile, MediaStatusEnum::FAILED));

            throw $e;
        } finally {
            @unlink($localSource);
            @unlink($localOutput);
        }
    }

    private function withStatus(MediaFile $mediaFile, MediaStatusEnum $status, ?string $path = null, ?int $durationSeconds = null): MediaFile
    {
        return new MediaFile(
            companyId: $mediaFile->companyId,
            filename: $mediaFile->filename,
            fileSize: $mediaFile->fileSize,
            fileType: $mediaFile->fileType,
            mediaType: $mediaFile->mediaType,
            storage: $mediaFile->storage,
            path: $path ?? $mediaFile->path,
            createdBy: $mediaFile->createdBy,
            createdAt: $mediaFile->createdAt,
            updatedBy: $mediaFile->updatedBy,
            deletedBy: $mediaFile->deletedBy,
            updatedAt: $mediaFile->updatedAt,
            deletedAt: $mediaFile->deletedAt,
            id: $mediaFile->getId(),
            status: $status,
            durationSeconds: $durationSeconds ?? $mediaFile->durationSeconds,
        );
    }
}
