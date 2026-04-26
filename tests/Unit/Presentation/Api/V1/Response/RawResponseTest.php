<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Response;

use PHPUnit\Framework\TestCase;
use Saso\Presentation\Api\V1\Response\RawResponse;

final class RawResponseTest extends TestCase
{
    public function testEmitWritesBodyVerbatim(): void
    {
        $body = "openapi: 3.1.0\nfoo: bar\n";

        $r = new RawResponse(200, $body, 'application/yaml');

        ob_start();
        $r->emit();
        $output = ob_get_clean();

        self::assertSame($body, $output);
    }
}
