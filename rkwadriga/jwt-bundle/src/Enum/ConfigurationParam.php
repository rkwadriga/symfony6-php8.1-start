<?php declare(strict_types=1);
/**
 * Created 2021-12-15
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Enum;

enum ConfigurationParam: string
{
    private const BASE_PATH = 'rkwadriga.jwt';

    case PROVIDER = 'provider';
    case USER_IDENTIFIER = 'user_identifier';
    case LOGIN_URL = 'login_url';
    case REFRESH_URL = 'refresh_url';
    case LOGIN_PARAM = 'login_pram';
    case PASSWORD_PARAM = 'password_param';
    case SECRET_KEY = 'secret_key';
    case ENCODING_ALGORITHM = 'encoding_algorithm';
    case ENCODING_HASHING_COUNT = 'encoding_hashing_count';
    case ACCESS_TOKEN_LIFE_TIME = 'access_token_life_time';
    case REFRESH_TOKEN_LIFE_TIME = 'refresh_token_life_time';
    case ACCESS_TOKEN_LOCATION = 'access_token_location';
    case ACCESS_TOKEN_PARAM_NAME = 'access_token_param_name';
    case REFRESH_TOKEN_LOCATION = 'refresh_token_location';
    case REFRESH_TOKEN_PARAM_NAME = 'refresh_token_param_name';
    case TOKEN_TYPE = 'token_type';
    case REFRESH_TOKEN_IN_DB = 'refresh_tokens_in_db';
    case REFRESH_TOKEN_TABLE = 'refresh_tokens_table';
    case REFRESH_TOKENS_LIMIT = 'refresh_tokens_limit';
    case REWRITE_ON_LIMIT_EXCEEDED = 'rewrite_on_limit_exceeded';

    public function shortValue(): string
    {
        return $this->value;
    }

    public function value(): string
    {
        return self::BASE_PATH . '.' . $this->value;
    }
}