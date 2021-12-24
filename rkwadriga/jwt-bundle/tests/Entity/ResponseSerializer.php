<?php declare(strict_types=1);
/**
 * Created 2021-12-24
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Entity;

use Symfony\Component\Serializer\SerializerInterface;

class ResponseSerializer implements SerializerInterface
{
    public function serialize(mixed $data, string $format, array $context = []): string
    {
        return '';
    }

    public function deserialize(mixed $data, string $type, string $format, array $context = []): mixed
    {
        return [];
    }
}