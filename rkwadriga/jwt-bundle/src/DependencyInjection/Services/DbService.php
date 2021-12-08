<?php declare(strict_types=1);
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Services;

use DateTimeImmutable;
use Rkwadriga\JwtBundle\DependencyInjection\Services\Db\CreateTableTrait;
use Rkwadriga\JwtBundle\DependencyInjection\Services\Db\ReadQueriesTrait;
use Rkwadriga\JwtBundle\DependencyInjection\Services\Db\WriteQueriesTrait;
use Rkwadriga\JwtBundle\Exceptions\DbServiceException;
use Rkwadriga\JwtBundle\Entity\TokenInterface;
use Doctrine\ORM\EntityManagerInterface;
use Rkwadriga\JwtBundle\Helpers\TokenHelper;

class DbService
{
    use CreateTableTrait;
    use ReadQueriesTrait;
    use WriteQueriesTrait;

    public function __construct(
        private EntityManagerInterface $em,
        private bool $isEnabled,
        private string $table,
        private int $tokensLimit,
        private string $userIdentifier,
        private bool $rewriteOnLimitExceeded
    ) {}

    public function checkTokensLimit(array $payload): void
    {
        // Check if there is some tokens limit. If not - there is nothing to do here
        if ($this->tokensLimit === 0) {
            return;
        }

        // Check if DB-module is enabled and create a table "refresh_token" if it doesn't exist
        if (!$this->init()) {
            return;
        }

        // User identifier (by default "email") is required in payload for working with refresh_token records
        $userID = $this->getUserID($payload);

        // Refresh tokens limit is not exceeded yet? good, go on...
        $recordsCount = $this->selectCountForUser($userID);
        if ($recordsCount < $this->tokensLimit) {
            return;
        }

        // Limit of refresh_tokens can not be exceeded
        if (!$this->rewriteOnLimitExceeded) {
            throw new DbServiceException("User {$userID} already has {$this->tokensLimit} tokens", DbServiceException::TOKENS_COUNT_EXCEEDED);
        }

        // Remove the oldest refresh_tokens
        $this->deleteOldestRecord($userID);
    }

    public function writeToken(TokenInterface $token, array $payload): void
    {
        // Check if DB-module is enabled and create a table "refresh_token" if it doesn't exist
        if (!$this->init()) {
            return;
        }

        // User identifier (by default "email") is required in payload for working with refresh_token records
        $userID = $this->getUserID($payload);

        // Refresh token is also required for writing it to the database...
        if ($token->getRefreshToken() === null) {
            throw new DbServiceException('Refresh token missed', DbServiceException::REFRESH_TOKEN_MISSED);
        }

        // Prepare token to get it's signature
        $payload = TokenHelper::parse($token->getRefreshToken(), TokenInterface::REFRESH);


        // Add token record
        $this->addNewRecord($userID, end($payload), DateTimeImmutable::createFromInterface($token->getCreatedAt()));
    }

    private function init(): bool
    {
        if (!$this->isEnabled) {
            return false;
        }

        $this->createTable();

        return true;
    }

    private function getUserID(array $payload): string|int
    {
        // The user identifier is required for payload
        if (!isset($payload[$this->userIdentifier])) {
            throw new DbServiceException(
                "User identifier \"{$this->userIdentifier}\" is not presented in token payload",
                DbServiceException::INVALID_PAYLOAD
            );
        }

        // User identifier can not be greater than 64, so create a hash if it is
        $userID = $payload[$this->userIdentifier];
        if (is_string($userID) && strlen($userID) > 64) {
            $userID = hash('SHA256', $userID);
        }

        return $userID;
    }
}