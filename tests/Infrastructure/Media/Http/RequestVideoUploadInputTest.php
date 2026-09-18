<?php

namespace App\Tests\Infrastructure\Media\Http;

use App\Infrastructure\Media\Http\RequestVideoUploadInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestVideoUploadInputTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testAValidPayloadHasNoViolations(): void
    {
        $violations = $this->validator->validate(new RequestVideoUploadInput(mimeType: 'video/mp4', fileSize: 1234));

        self::assertCount(0, $violations);
    }

    public function testABlankMimeTypeIsRejected(): void
    {
        $violations = $this->validator->validate(new RequestVideoUploadInput(mimeType: '', fileSize: 1234));

        $messages = array_map(static fn ($v) => $v->getMessage(), iterator_to_array($violations));
        self::assertContains('media.mime_type.not_blank', $messages);
    }

    public function testADisallowedMimeTypeIsRejected(): void
    {
        $violations = $this->validator->validate(new RequestVideoUploadInput(mimeType: 'image/jpeg', fileSize: 1234));

        $messages = array_map(static fn ($v) => $v->getMessage(), iterator_to_array($violations));
        self::assertContains('media.file.invalid_type', $messages);
    }

    public function testANonPositiveFileSizeIsRejected(): void
    {
        $violations = $this->validator->validate(new RequestVideoUploadInput(mimeType: 'video/mp4', fileSize: 0));

        $messages = array_map(static fn ($v) => $v->getMessage(), iterator_to_array($violations));
        self::assertContains('media.file_size.positive', $messages);
    }
}
