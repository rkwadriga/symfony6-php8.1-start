<?php declare(strict_types=1);
/**
 * Created 2021-12-19
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Unit;

use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Enum\TokenParamLocation;
use Rkwadriga\JwtBundle\Enum\TokenParamType;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/Unit/ConfigServiceTest.php
 */
class ConfigServiceTest extends AbstractUnitTestCase
{
    public function testDefaults(): void
    {
        $configService = $this->getConfigService();

        $getDefault = function (ConfigurationParam $param) {
            return match ($param) {
                ConfigurationParam::PROVIDER => 'app_user_provider',
                ConfigurationParam::LOGIN_URL => 'rkwadriga_jwt_auth_login',
                ConfigurationParam::REFRESH_URL => 'rkwadriga_jwt_refresh_token',
                ConfigurationParam::USER_IDENTIFIER => 'email',
                ConfigurationParam::LOGIN_PARAM => 'email',
                ConfigurationParam::PASSWORD_PARAM => 'password',
                ConfigurationParam::SECRET_KEY => 'Lm870sdfpOki78Yr6Tsdfkl09Iksdjf71sdfk',
                ConfigurationParam::ENCODING_ALGORITHM => 'SHA256',
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
                ConfigurationParam::REFRESH_TOKENS_LIMIT => 10,
                ConfigurationParam::REWRITE_ON_LIMIT_EXCEEDED => true,
            };
        };

        foreach (ConfigurationParam::cases() as $case) {
            [$param, $default, $actual] = [$case->value, $getDefault($case), $configService->get($case)];
            if (is_integer($default)) {
                $defaultValueString = $default;
            } elseif ($default === true) {
                $defaultValueString = 'TRUE';
            } elseif ($default === false) {
                $defaultValueString = 'FALSE';
            } else {
                $defaultValueString = "\"{$default}\"";
            }
            $this->assertEquals($default, $actual, "Param \"{$param}\" must have value {$defaultValueString}");
        }
    }
}