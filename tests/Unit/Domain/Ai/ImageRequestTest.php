<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Ai;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Ai\ImageRequest;

final class ImageRequestTest extends TestCase
{
    public function testStoresFields(): void
    {
        $r = new ImageRequest(
            imageBytes: "\xff\xd8\xff",
            prompt: 'Describe this product.',
            mimeType: 'image/png',
        );

        self::assertSame("\xff\xd8\xff", $r->imageBytes);
        self::assertSame('Describe this product.', $r->prompt);
        self::assertSame('image/png', $r->mimeType);
    }

    public function testRejectsEmptyImageBytes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ImageRequest(imageBytes: '', prompt: 'x');
    }

    public function testRejectsEmptyPrompt(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ImageRequest(imageBytes: 'b', prompt: '');
    }

    public function testRejectsNonPositiveMaxTokens(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ImageRequest(imageBytes: 'b', prompt: 'p', maxTokens: 0);
    }
}
