<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Rkwadriga\JwtBundle\Enum\TokenCreationContext;

abstract class AbstractTokenCreatingEvent extends AbstractTokenEvent
{
    public function __construct(
        private TokenCreationContext $creationContext
    ) {}

    public function getCreationContext(): TokenCreationContext
    {
        return $this->creationContext;
    }
}