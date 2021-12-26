<?php
/**
 * Created 2021-12-26
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use Exception;

trait CustomAssertionsTrait
{
    protected function compareExceptions(string $baseMessage, Exception $actual, Exception $expected, ?Exception $previous = null): void
    {
        $this->assertInstanceOf($expected::class, $actual,
            $baseMessage . 'Exception has an invalid type: ' . $actual::class
        );
        $this->assertSame($expected->getMessage(), $actual->getMessage(),
            $baseMessage . 'Exception has an invalid message: ' . $actual->getMessage()
        );
        $this->assertSame($expected->getCode(), $actual->getCode(),
            $baseMessage . 'Exception has an invalid code: ' . $actual->getCode()
        );
        if ($previous !== null) {
            $this->assertNotNull($actual->getPrevious(),
                $baseMessage . 'Exception has no previous exception'
            );
            $this->assertInstanceOf($previous::class, $actual->getPrevious(),
                $baseMessage . 'Exception previous has an invalid type: ' . $actual->getPrevious()::class
            );
        }
    }
}