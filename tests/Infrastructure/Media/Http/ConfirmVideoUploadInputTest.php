<?php

namespace App\Tests\Infrastructure\Media\Http;

use App\Infrastructure\Media\Http\ConfirmVideoUploadInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ConfirmVideoUploadInputTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testAValidFilenameHasNoViolations(): void
    {
        $violations = $this->validator->validate(new ConfirmVideoUploadInput(filename: 'clip.mp4'));

        self::assertCount(0, $violations);
    }

    public function testABlankFilenameIsRejected(): void
    {
        $violations = $this->validator->validate(new ConfirmVideoUploadInput(filename: ''));

        $messages = array_map(static fn ($v) => $v->getMessage(), iterator_to_array($violations));
        self::assertContains('media.filename.not_blank', $messages);
    }
}
