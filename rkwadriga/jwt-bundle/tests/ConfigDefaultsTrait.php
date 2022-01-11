<?php
/**
 * Created 2021-12-20
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Enum\TokenParamLocation;
use Rkwadriga\JwtBundle\Enum\TokenParamType;

trait ConfigDefaultsTrait
{
    private array $customConfig = [];

    protected function getConfigDefault(ConfigurationParam $param)
    {
        if (isset($this->customConfig[$param->value])) {
            return $this->customConfig[$param->value];
        }

        return match ($param) {
            ConfigurationParam::PROVIDER => 'rkwadriga_jwt_default_user_provider',
            ConfigurationParam::USER_IDENTIFIER => 'email',
            ConfigurationParam::LOGIN_URL => 'rkwadriga_jwt_auth_login',
            ConfigurationParam::REFRESH_URL => 'rkwadriga_jwt_refresh_token',
            ConfigurationParam::LOGIN_PARAM => 'email',
            ConfigurationParam::PASSWORD_PARAM => 'password',
            ConfigurationParam::SECRET_KEY => self::$secretKey,
            ConfigurationParam::ENCODING_ALGORITHM => Algorithm::SHA256->value,
            ConfigurationParam::ENCODING_HASHING_COUNT => 3,
            ConfigurationParam::ACCESS_TOKEN_LIFE_TIME => 3600,
            ConfigurationParam::REFRESH_TOKEN_LIFE_TIME => 15552000,
            ConfigurationParam::ACCESS_TOKEN_LOCATION => TokenParamLocation::HEADER->value,
            ConfigurationParam::ACCESS_TOKEN_PARAM_NAME => 'Authorization',
            ConfigurationParam::REFRESH_TOKEN_LOCATION => TokenParamLocation::BODY->value,
            ConfigurationParam::REFRESH_TOKEN_PARAM_NAME => 'refresh_token',
            ConfigurationParam::TOKEN_TYPE => TokenParamType::BEARER->value,
            ConfigurationParam::REFRESH_TOKEN_IN_DB => true,
            ConfigurationParam::REFRESH_TOKEN_TABLE => 'refresh_token',
            ConfigurationParam::REFRESH_TOKENS_LIMIT => 3,
            ConfigurationParam::REWRITE_ON_LIMIT_EXCEEDED => true,
        };
    }

    protected function setConfigDefault(ConfigurationParam $param, mixed $vale): void
    {
        $this->customConfig[$param->value] = $vale;
    }
}