<?php

namespace App\Application\Media;

use App\Application\Shared\IdGeneratorInterface;
use App\Application\Shared\TransactionInterface;
use App\Domain\Media\MediaFile;
use App\Domain\Media\MediaFileRepositoryInterface;
use App\Domain\Media\StorageProviderEnum;
use App\Domain\Subscription\SubscriptionRepositoryInterface;

class UploadMediaFile
{
    public function __construct(
        private FileStorageInterface $fileStorage,
        private MediaFileRepositoryInterface $mediaFileRepository,
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private TransactionInterface $transaction,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function execute(UploadMediaFileRequest $request): MediaFile
    {
        $subscription = $this->subscriptionRepository->findByCompanyId($request->companyId);
        if (null === $subscription) {
            throw new SubscriptionNotFoundException('No active subscription found for this company.');
        }

        if (!$subscription->hasStorageFor($request->fileSize)) {
            throw new StorageQuotaExceededException('Storage quota exceeded for this company.');
        }

        $id = $this->idGenerator->generate();
        $path = \sprintf('company/%s/media/%s', $request->companyId, $id.$this->extensionOf($request->originalFilename));

        $this->fileStorage->upload($request->localFilePath, $path, $request->mimeType);

        $mediaFile = new MediaFile(
            companyId: $request->companyId,
            filename: $request->originalFilename,
            fileSize: $request->fileSize,
            fileType: $request->mimeType,
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

    private function extensionOf(string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        return '' === $extension ? '' : '.'.$extension;
    }
}
