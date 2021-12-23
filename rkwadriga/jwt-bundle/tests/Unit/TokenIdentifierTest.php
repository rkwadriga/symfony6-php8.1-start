<?php declare(strict_types=1);
/**
 * Created 2021-12-22
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Unit;

use Exception;
use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Enum\TokenParamLocation;
use Rkwadriga\JwtBundle\Enum\TokenParamType;
use Rkwadriga\JwtBundle\Exception\TokenIdentifierException;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/Unit/TokenIdentifierTest.php
 */
class TokenIdentifierTest extends AbstractUnitTestCase
{
    public function testIdentify(): void
    {
        $configParams = [
            ConfigurationParam::ACCESS_TOKEN_PARAM_NAME->value => TokenType::ACCESS->value,
            ConfigurationParam::REFRESH_TOKEN_PARAM_NAME->value => TokenType::REFRESH->value,
        ];

        // For all token types...
        foreach (TokenType::cases() as $tokenType) {
            // For all encoding algorithms...
            foreach (Algorithm::cases() as $algorithm) {
                $userID = $tokenType->value . '_' . $algorithm->value;
                $controlToken = $this->createToken($algorithm, $tokenType, $userID);

                // For all token param locations (where it is in request)...
                foreach (TokenParamLocation::cases() as $tokenParamLocation) {
                    // For all token param names (how it named in request)...
                    foreach (TokenParamType::cases() as $tokenParamType) {
                        // For different content types...
                        foreach (['json', 'form'] as $contentType) {
                            $testCaseBaseError = "Test testIdentify case \"{$tokenType->value}_{$algorithm->value}_{$tokenParamLocation->value}_{$tokenParamType->value}_{$contentType}\" failed: ";

                            // Create services methods mocks
                            $headersMethodsMock = [
                                'get' => $tokenParamType === TokenParamType::BEARER ? 'Bearer ' . $controlToken->getToken() : $controlToken->getToken(),
                            ];
                            $requestMethodsMock = [
                                'getContentType' => $contentType,
                                'get' => $controlToken->getToken(),
                                'getContent' => json_encode([$tokenType->value => $controlToken->getToken()]),
                            ];
                            $configMethodsMock = array_merge($configParams, [
                                ConfigurationParam::ACCESS_TOKEN_LOCATION->value => $tokenParamLocation->value,
                                ConfigurationParam::REFRESH_TOKEN_LOCATION->value => $tokenParamLocation->value,
                                ConfigurationParam::TOKEN_TYPE->value => $tokenParamType->value,
                            ]);

                            // Mock headers
                            $headers = $this->createMock(HeaderBag::class, $headersMethodsMock);
                            // Mock request
                            $request = $this->createMock(Request::class, $requestMethodsMock);
                            $request->headers = $headers;
                            // Mock config service
                            $configService = $this->mockConfigService($configMethodsMock);

                            // Create "TokenIdentifier" service
                            $identifierService = $this->createTokenIdentifierInstance($configService);

                            // Check correct token identifying
                            $token = $identifierService->identify($request, $tokenType);
                            $this->assertSame($controlToken->getToken(), $token, $testCaseBaseError . 'Invalid token was found');

                            // Check "Token not found" exception
                            $invalidHeadersMethodsMock = array_merge($headersMethodsMock, ['get' => null]);
                            $invalidRequestMethodsMock = array_merge($requestMethodsMock, ['get' => null, 'getContent' => "{}}"]);
                            $request = $this->createMock(Request::class, $invalidRequestMethodsMock);
                            $request->headers = $this->createMock(HeaderBag::class, $invalidHeadersMethodsMock);
                            $identifierService = $this->createTokenIdentifierInstance($this->mockConfigService($configMethodsMock));

                            $exceptionWasThrown = false;
                            try {
                                $identifierService->identify($request, $tokenType);
                            } catch (Exception $e) {
                                $exceptionWasThrown = true;
                                $code = $tokenType === TokenType::ACCESS ? TokenIdentifierException::ACCESS_TOKEN_MISSED : TokenIdentifierException::REFRESH_TOKEN_MISSED;
                                $this->assertInstanceOf(TokenIdentifierException::class, $e, $testCaseBaseError . '"Token not found" exception has an invalid type: ' . $e::class);
                                $this->assertSame($code, $e->getCode(), $testCaseBaseError . "\"Token not found\" exception has an invalid code: \"{$e->getCode()}\"");
                            }
                            if (!$exceptionWasThrown) {
                                $this->assertEquals(0 ,1, $testCaseBaseError . '"Token not found" exception was not thrown');
                            }

                            // Check "Invalid token" exception
                            [$invalidHeadersMethodsMock, $invalidRequestMethodsMock] = [$headersMethodsMock, $requestMethodsMock];
                            if ($tokenType === TokenType::ACCESS && $tokenParamLocation === TokenParamLocation::HEADER) {
                                $invalidHeadersMethodsMock['get'] = null;
                                $invalidRequestMethodsMock['get'] = null;
                                $invalidRequestMethodsMock['getContent'] = json_encode(['invalid_param_name' => $controlToken->getToken()]);
                            }
                            $request = $this->createMock(Request::class, $invalidRequestMethodsMock);
                            $request->headers = $this->createMock(HeaderBag::class, $invalidHeadersMethodsMock);
                            $identifierService = $this->createTokenIdentifierInstance($this->mockConfigService($configMethodsMock));

                            $exceptionWasThrown = false;
                            try {
                                $identifierService->identify($request, $tokenType);
                            } catch (Exception $e) {
                                if ($tokenType !== TokenType::ACCESS || $tokenParamLocation !== TokenParamLocation::HEADER) {
                                    throw $e;
                                }
                                $exceptionWasThrown = true;
                                $code = $tokenType === TokenType::ACCESS ? TokenIdentifierException::ACCESS_TOKEN_MISSED : TokenIdentifierException::REFRESH_TOKEN_MISSED;
                                $this->assertInstanceOf(TokenIdentifierException::class, $e, $testCaseBaseError . '"Invalid token" exception has an invalid type: ' . $e::class);
                                $this->assertSame($code, $e->getCode(), $testCaseBaseError . "\"Invalid token\" exception has an invalid code: \"{$e->getCode()}\"");
                            }

                            if ($tokenType === TokenType::ACCESS && $tokenParamLocation === TokenParamLocation::HEADER && !$exceptionWasThrown) {
                                $this->assertEquals(0 ,1, $testCaseBaseError . '"Invalid token" exception was not thrown');
                            }
                        }
                    }
                }
            }
        }
    }
}