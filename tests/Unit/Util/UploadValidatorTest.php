<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use saso\util\monad\Either;
use saso\util\monad\Left;
use saso\util\UploadValidator;

/*
 * Unit-test the validator's pre-`is_uploaded_file()` checks. Steps that depend
 * on the SAPI upload machinery (`is_uploaded_file`, `finfo_file`,
 * `getimagesize`) are exercised by the future integration suite (M5) since
 * they require an actual multipart request through a web server.
 */
#[CoversClass(UploadValidator::class)]
final class UploadValidatorTest extends TestCase
{
    private const ALLOWED = ['image/png', 'image/jpeg', 'image/gif'];

    public function testRejectsWhenRequiredKeysAreMissing(): void
    {
        $result = UploadValidator::validateImageUpload([], self::ALLOWED);
        self::assertInstanceOf(Left::class, $result);
        self::assertSame('upload payload missing', $this->leftValue($result));
    }

    public function testRejectsWhenErrorIsNotOk(): void
    {
        $result = UploadValidator::validateImageUpload(
            [
                'tmp_name' => '/tmp/whatever',
                'size'     => 100,
                'error'    => UPLOAD_ERR_INI_SIZE,
            ],
            self::ALLOWED,
        );
        self::assertInstanceOf(Left::class, $result);
        self::assertStringStartsWith('upload error code ', (string) $this->leftValue($result));
    }

    public function testRejectsWhenTmpNameIsNotAnUploadedFile(): void
    {
        // is_uploaded_file() returns false for any path outside the SAPI upload
        // table, including arbitrary tmp paths created by the test. This is the
        // exact attack we want to block: caller-supplied tmp_name pointing at
        // some other file on disk.
        $result = UploadValidator::validateImageUpload(
            [
                'tmp_name' => __FILE__,
                'size'     => filesize(__FILE__) ?: 0,
                'error'    => UPLOAD_ERR_OK,
            ],
            self::ALLOWED,
        );
        self::assertInstanceOf(Left::class, $result);
        self::assertSame('not an uploaded file', $this->leftValue($result));
    }

    /**
     * @return mixed
     */
    private function leftValue(Either $either)
    {
        // util\monad\Left exposes its value through orElse(callable).
        $captured = null;
        $either->orElse(function ($v) use (&$captured) {
            $captured = $v;
            return $v;
        });
        return $captured;
    }
}
