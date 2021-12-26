<?php
/**
 * Created 2021-12-20
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use ReflectionClass;

trait ReflectionTrait
{
    protected function callPrivateMethod(object $obj, string $method, array $arguments = []): mixed
    {
        $reflection = new ReflectionClass($obj::class);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $arguments);
    }

    protected function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflectionClass = new ReflectionClass($object::class);
        $reflectionClass->getProperty($property)->setValue($object, $value);
    }
}