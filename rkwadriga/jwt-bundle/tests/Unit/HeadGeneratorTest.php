<?php declare(strict_types=1);
/**
 * Created 2021-12-21
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Unit;

use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Service\HeadGenerator;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/Unit/HeadGeneratorTest.php
 */
class HeadGeneratorTest extends AbstractUnitTestCase
{
    public function testGenerate(): void
    {
        // For all token types...
        foreach (TokenType::cases() as $tokenType) {
            // For all algorithms
            foreach (Algorithm::cases() as $algorithm) {
                $config = $this->mockConfigService([ConfigurationParam::ENCODING_ALGORITHM->value => $algorithm->value]);
                $headGenerator = $this->createHeadGeneratorInstance($config);
                // For different payloads...
                for ($i = 1; $i <= 3; $i++) {
                    $errorStartMsg = "Test case \"{$tokenType->value}_{$algorithm->value}_{$i}\" failed: ";

                    $payload = [
                        "int_pram_{$i}" => $i,
                        "string_pram_{$i}" => "String Value {$i}",
                        "float_pram_{$i}" => $i / 2,
                    ];
                    if ($i % 2 === 0) {
                        $payload["array_pram_{$i}"] = ['int_param' => $i, 'string_param' => "String Value {$i}"];
                    }
                    // For defined and not defined algorithms generate and check head
                    $head1 = $headGenerator->generate($payload, $tokenType, $algorithm);
                    $head2 = $headGenerator->generate($payload, $tokenType);

                    $this->assertSame($head1, $head2, $errorStartMsg . 'heads for defined and not defined algorithms area not same');
                    $this->assertArrayHasKey('alg', $head2, $errorStartMsg . 'head has no "alg" key');
                    $this->assertSame($algorithm->value, $head2['alg'], $errorStartMsg . 'head has invalid "alg" value');
                    $this->assertArrayHasKey('typ', $head2, $errorStartMsg . 'head has no "typ" key');
                    $this->assertSame(HeadGenerator::TOKEN_TYPE, $head2['typ'], $errorStartMsg . 'head has invalid "typ" value');
                    $this->assertArrayHasKey('sub', $head2, $errorStartMsg . 'head has no "sub" key');
                    $this->assertSame($tokenType->value, $head2['sub'], $errorStartMsg . 'head has invalid "sub" value');
                }
            }
        }
    }
}