<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use saso\auth\AuthController;

final class AuthControllerTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string}> */
    public static function restoredPathSamples(): array
    {
        return [
            'relative path preserved'             => ['item/list/', 'item/list/'],
            'error marker stripped'               => ['item/list/error/1/', 'item/list/'],
            'absolute https URL rejected'         => ['https://attacker.example/login', 'start/start/'],
            'absolute custom scheme rejected'     => ['javascript:alert(1)', 'start/start/'],
            'protocol-relative URL rejected'      => ['//attacker.example/login', 'start/start/'],
            'protocol-relative after error strip' => ['//attacker.example/login/error/1/', 'start/start/'],
        ];
    }

    /**
     * @dataProvider restoredPathSamples
     */
    public function testRestoredPathRejectsAbsoluteUrls(string $input, string $expected): void
    {
        $controller = new AuthController(['restoredPath' => $input]);

        self::assertSame($expected, $this->readAuthInputProperty($controller, 'restoredPath'));
    }

    public function testRestoredPathDefaultsWhenMissing(): void
    {
        $controller = new AuthController([]);

        self::assertSame('start/start/', $this->readAuthInputProperty($controller, 'restoredPath'));
    }

    /** @return mixed */
    private function readAuthInputProperty(AuthController $controller, string $property)
    {
        $controllerReflection = new \ReflectionClass($controller);
        $dataProperty         = $controllerReflection->getProperty('data');
        $dataProperty->setAccessible(true);
        $data = $dataProperty->getValue($controller);

        $inputReflection = new \ReflectionClass($data);
        $inputProperty   = $inputReflection->getProperty($property);
        $inputProperty->setAccessible(true);

        return $inputProperty->getValue($data);
    }
}
