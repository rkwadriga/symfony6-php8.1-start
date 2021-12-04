<?php declare(strict_types=1);
/**
 * Created 2021-12-04
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Api\Helpers;

use Symfony\Component\HttpFoundation\Response;

class HttpResponse
{
    public function __construct(
        private int $status,
        private ?array $body,
        private bool $successful = false,
        private ?string $error = null
    ) {
        $this->successful = in_array($this->status, [Response::HTTP_OK, Response::HTTP_CREATED, Response::HTTP_NO_CONTENT]);

        if (
            !$this->successful &&
            $this->body !== null &&
            (isset($this->body['message']) || isset($this->body['error']) || isset($this->body['info']))
        ) {
            if (isset($this->body['message'])) {
                $this->error = $this->body['message'];
            } elseif (isset($this->body['error'])) {
                $this->error = $this->body['error'];
            } elseif (isset($this->body['info'])) {
                $this->error = $this->body['info'];
            }
            if ($this->error !== null && isset($this->body['code'])) {
                $this->error .= sprintf(' (code: %s)', $this->body['code']);
            }
        }
    }
}