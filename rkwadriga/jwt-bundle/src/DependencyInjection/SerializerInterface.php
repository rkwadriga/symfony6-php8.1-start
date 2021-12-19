<?php
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection;

interface SerializerInterface
{
    public function encode(string $data): string;

    public function decode(string $data): string;

    public function signature(string $data, ?Algorithm $algorithm = null): string;

    public function serialize(array $data): string;

    public function deserialiaze(string $data): array;

    public function implode(array $data): string;

    public function explode(string $data): array;
}