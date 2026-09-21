<?php

namespace App\Application\Media;

use App\Application\Shared\TransactionInterface;
use App\Domain\Media\MediaFile;
use App\Domain\Media\MediaFilePathBuilder;
use App\Domain\Media\MediaFileRepositoryInterface;
use App\Domain\Media\MediaStatusEnum;
use App\Domain\Media\MediaTypeEnum;
use App\Domain\Media\StorageProviderEnum;
use App\Domain\Subscription\SubscriptionRepositoryInterface;

class ConfirmVideoUpload
{
    public function __construct(
        private MediaObjectMetadataReaderInterface $mediaObjectMetadataReader,
        private FileStorageInterface $fileStorage,
        private MediaFileRepositoryInterface $mediaFileRepository,
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private VideoTranscodeSchedulerInterface $videoTranscodeScheduler,
        private TransactionInterface $transaction,
    ) {
    }

    public function execute(ConfirmVideoUploadRequest $request): MediaFile
    {
        $existing = $this->mediaFileRepository->findById($request->mediaId);
        if (null !== $existing) {
            if ($existing->companyId !== $request->companyId) {
                throw new MediaFileNotFoundException('Media file not found.');
            }

            return $existing;
        }

        $path = MediaFilePathBuilder::build($request->companyId, $request->mediaId);

        try {
            $metadata = $this->mediaObjectMetadataReader->read($path);

            try {
                $mediaType = MediaTypeEnum::fromMimeType($metadata->contentType);
            } catch (\InvalidArgumentException $e) {
                throw new UnsupportedMediaTypeException($e->getMessage());
            }

            if (MediaTypeEnum::VIDEO !== $mediaType) {
                throw new UnsupportedMediaTypeException(\sprintf('Expected a video, got "%s".', $metadata->contentType));
            }

            if ($metadata->contentLength > $mediaType->maxFileSizeInBytes()) {
                throw new FileTooLargeException(\sprintf('File exceeds the maximum size allowed for %s files.', $mediaType->value));
            }

            $subscription = $this->subscriptionRepository->findByCompanyId($request->companyId);
            if (null === $subscription) {
                throw new SubscriptionNotFoundException('No active subscription found for this company.');
            }

            if (!$subscription->hasStorageFor($metadata->contentLength)) {
                throw new StorageQuotaExceededException('Storage quota exceeded for this company.');
            }

            $mediaFile = new MediaFile(
                companyId: $request->companyId,
                filename: $request->originalFilename,
                fileSize: $metadata->contentLength,
                fileType: $metadata->contentType,
                mediaType: $mediaType,
                storage: StorageProviderEnum::S3,
                path: $path,
                createdBy: $request->userId,
                createdAt: new \DateTimeImmutable(),
                id: $request->mediaId,
                status: MediaStatusEnum::PROCESSING,
            );

            $this->transaction->transactional(function () use ($mediaFile, $request): void {
                $this->mediaFileRepository->save($mediaFile);
                $this->subscriptionRepository->incrementStorageUsedBytes($request->companyId, $mediaFile->fileSize);
            });

            $this->videoTranscodeScheduler->schedule($request->mediaId);
        } catch (MediaObjectNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->fileStorage->delete($path);

            throw $e;
        }

        return $mediaFile;
    }
}
