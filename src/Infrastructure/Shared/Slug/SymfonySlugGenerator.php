<?php

namespace App\Infrastructure\Shared\Slug;

use App\Application\Shared\SlugGeneratorInterface;
use Override;
use Symfony\Component\String\Slugger\AsciiSlugger;

class SymfonySlugGenerator implements SlugGeneratorInterface
{
    private AsciiSlugger $slugger;

    public function __construct()
    {
        $this->slugger = new AsciiSlugger();
    }

    #[Override]
    public function slugify(string $text): string
    {
        return strtolower((string) $this->slugger->slug($text));
    }
}
