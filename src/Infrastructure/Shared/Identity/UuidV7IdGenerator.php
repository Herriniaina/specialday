<?php

namespace App\Infrastructure\Shared\Identity;

use App\Application\Shared\IdGeneratorInterface;
use Override;
use Ramsey\Uuid\Uuid;

class UuidV7IdGenerator implements IdGeneratorInterface
{
    #[Override]
    public function generate(): string
    {
        return Uuid::uuid7()->toString();
    }
}
