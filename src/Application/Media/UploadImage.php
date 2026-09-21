<?php

namespace App\Application\Media;

use App\Application\Shared\IdGeneratorInterface;
use App\Application\Shared\TransactionInterface;
use App\Domain\Media\MediaFile;
use App\Domain\Media\MediaFilePathBuilder;
use App\Domain\Media\MediaFileRepositoryInterface;
use App\Domain\Media\MediaTypeEnum;
use App\Domain\Media\StorageProviderEnum;
use App\Domain\Subscription\SubscriptionRepositoryInterface;

class UploadImage
{
    public function __construct(
        private FileStorageInterface $fileStorage,
        private MediaFileRepositoryInterface $mediaFileRepository,
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private TransactionInterface $transaction,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function execute(UploadImageRequest $request): MediaFile
    {
        $mediaType = MediaTypeEnum::fromMimeType($request->mimeType);
        if ($request->fileSize > $mediaType->maxFileSizeInBytes()) {
            throw new FileTooLargeException(\sprintf('File exceeds the maximum size allowed for %s files.', $mediaType->value));
        }

        $subscription = $this->subscriptionRepository->findByCompanyId($request->companyId);
        if (null === $subscription) {
            throw new SubscriptionNotFoundException('No active subscription found for this company.');
        }

        if (!$subscription->hasStorageFor($request->fileSize)) {
            throw new StorageQuotaExceededException('Storage quota exceeded for this company.');
        }

        $id = $this->idGenerator->generate();
        $path = MediaFilePathBuilder::build($request->companyId, $id);

        $this->fileStorage->upload($request->localFilePath, $path, $request->mimeType);

        $mediaFile = new MediaFile(
            companyId: $request->companyId,
            filename: $request->originalFilename,
            fileSize: $request->fileSize,
            fileType: $request->mimeType,
            mediaType: $mediaType,
            storage: StorageProviderEnum::S3,
            path: $path,
            createdBy: $request->userId,
            createdAt: new \DateTimeImmutable(),
            id: $id,
        );

        try {
            $this->transaction->transactional(function () use ($mediaFile, $request): void {
                $this->mediaFileRepository->save($mediaFile);
                $this->subscriptionRepository->incrementStorageUsedBytes($request->companyId, $request->fileSize);
            });
        } catch (\Throwable $e) {
            $this->fileStorage->delete($path);

            throw $e;
        }

        return $mediaFile;
    }
}
