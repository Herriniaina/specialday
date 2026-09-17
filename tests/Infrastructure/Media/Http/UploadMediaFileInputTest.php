<?php

namespace App\Tests\Infrastructure\Media\Http;

use App\Infrastructure\Media\Http\UploadMediaFileInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UploadMediaFileInputTest extends TestCase
{
    private ValidatorInterface $validator;

    /** @var string[] */
    private array $createdFiles = [];

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $path) {
            @unlink($path);
        }
    }

    private function uploadedFile(string $content, string $originalName): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'media_test_');
        \assert(false !== $path);
        file_put_contents($path, $content);
        $this->createdFiles[] = $path;

        return new UploadedFile($path, $originalName, null, null, true);
    }

    public function testAJpegFileHasNoViolations(): void
    {
        $jpeg = (string) hex2bin('FFD8FFE000104A46494600010100000100010000FFDB004300FFD9');
        $file = $this->uploadedFile($jpeg, 'photo.jpg');

        $violations = $this->validator->validate(new UploadMediaFileInput($file));

        self::assertCount(0, $violations);
    }

    public function testANullFileIsRejected(): void
    {
        $violations = $this->validator->validate(new UploadMediaFileInput());

        $messages = array_map(static fn ($v) => $v->getMessage(), iterator_to_array($violations));
        self::assertContains('media.file.not_blank', $messages);
    }

    public function testATextFileIsRejected(): void
    {
        $file = $this->uploadedFile('hello world', 'notes.txt');

        $violations = $this->validator->validate(new UploadMediaFileInput($file));

        $messages = array_map(static fn ($v) => $v->getMessage(), iterator_to_array($violations));
        self::assertContains('media.file.invalid_type', $messages);
    }
}
