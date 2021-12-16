<?php
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection;

interface EncoderInterface
{
    public function encode(array $head, array $payload, Algorithm $algorithm): string;
}