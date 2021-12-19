<?php declare(strict_types=1);
/**
 * Created 2021-12-17
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Service;

use Exception;
use DateTimeImmutable;
use Rkwadriga\JwtBundle\DependencyInjection\DbManagerInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenInterface;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Enum\TokenRefreshingContext;
use Rkwadriga\JwtBundle\Exception\DbServiceException;
use Rkwadriga\JwtBundle\Service\Db\CreateTableTrait;
use Rkwadriga\JwtBundle\Service\Db\ReadQueriesTrait;
use Rkwadriga\JwtBundle\Service\Db\WriteQueriesTrait;
use Doctrine\ORM\EntityManagerInterface;

class DbManager implements DbManagerInterface
{
    public function __construct(
        private Config $config,
        private EntityManagerInterface $em
    ) {
        try {
            $this->createTable();
        } catch (Exception $e) {
            $table = $this->config->get(ConfigurationParam::REFRESH_TOKEN_TABLE);
            throw new DbServiceException("Can not create \"{$table}\" table: ". $e->getMessage(), DbServiceException::CAN_NOT_CREATE_TABLE, $e);
        }
    }

    use CreateTableTrait;
    use ReadQueriesTrait;
    use WriteQueriesTrait;

    public function writeRefreshToken(TokenInterface $refreshToken, string|int $userID, TokenRefreshingContext $refreshingContext): void
    {
        // Check "refresh_token" records limit
        $recordsLimit = $this->config->get(ConfigurationParam::REFRESH_TOKENS_LIMIT);
        if ($recordsLimit > 0) {
            // Get "refresh_tokens" records count and check is limit not exceeded
            try {
                $recordsCount = $this->selectCountForUser($userID);
            } catch (Exception $e) {
                throw new DbServiceException('Can calculate "refresh_token" records count: '. $e->getMessage(), DbServiceException::SQL_ERROR, $e);
            }

            // Records limit exceeded
            if ($recordsCount >= $recordsLimit) {
                // Not rewrite records? Then throw an exception...
                if (!$this->config->get(ConfigurationParam::REWRITE_ON_LIMIT_EXCEEDED)) {
                    throw new DbServiceException('Refresh tokens count exceeded', DbServiceException::TOKENS_COUNT_EXCEEDED);
                }
                // Or delete the oldest record
                try {
                    $this->deleteOldestRecord($userID);
                } catch (Exception $e) {
                    throw new DbServiceException('Can not not delete the oldest "refresh_token" record: '. $e->getMessage(), DbServiceException::SQL_ERROR, $e);
                }
            }
        }

        // Write refresh token to table
        $this->addNewRecord($userID, $refreshToken->getSignature(), DateTimeImmutable::createFromInterface($refreshToken->getCreatedAt()));
    }

    public function isRefreshTokenExist(TokenInterface $refreshToken): bool
    {
        dd($refreshToken);
    }

    public function updateRefreshToken(TokenInterface $oldRefreshToken, TokenInterface $newRefreshToken): void
    {
        dd($oldRefreshToken, $newRefreshToken);
    }

}