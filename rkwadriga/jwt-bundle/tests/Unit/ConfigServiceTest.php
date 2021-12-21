<?php declare(strict_types=1);
/**
 * Created 2021-12-19
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Unit;

use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Enum\TokenParamLocation;
use Rkwadriga\JwtBundle\Enum\TokenParamType;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/Unit/ConfigServiceTest.php
 */
class ConfigServiceTest extends AbstractUnitTestCase
{
    public function testDefaults(): void
    {
        $configService = $this->createConfigServiceInstance();

        foreach (ConfigurationParam::cases() as $case) {
            [$param, $default, $actual] = [$case->value, $this->getConfigDefault($case), $configService->get($case)];
            if (is_integer($default)) {
                $defaultValueString = $default;
            } elseif ($default === true) {
                $defaultValueString = 'TRUE';
            } elseif ($default === false) {
                $defaultValueString = 'FALSE';
            } else {
                $defaultValueString = "\"{$default}\"";
            }
            $this->assertSame($default, $actual, "Param \"{$param}\" must have value {$defaultValueString}");
        }
    }
}