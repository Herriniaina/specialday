<?php

namespace App\Application\Media;

use App\Application\Shared\IdGeneratorInterface;
use App\Domain\Media\MediaFilePathBuilder;
use App\Domain\Media\MediaTypeEnum;
use App\Domain\Subscription\SubscriptionRepositoryInterface;

class RequestVideoUpload
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private VideoUploadUrlGeneratorInterface $videoUploadUrlGenerator,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function execute(RequestVideoUploadRequest $request): RequestVideoUploadResult
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

        return new RequestVideoUploadResult($id, $this->videoUploadUrlGenerator->generate($path));
    }
}
