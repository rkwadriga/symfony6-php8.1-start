<?php declare(strict_types=1);
/**
 * Created 2021-12-04
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Services;

use Rkwadriga\JwtBundle\Exceptions\KeyGeneratorException;

class Generator
{
    private const DEFAULT_LENGTH = 2048;
    private const DEFAULT_TYPE = OPENSSL_KEYTYPE_RSA;

    public function generate(string $algorithm = Encoder::DEFAULT_ALGORITHM, int $length = self::DEFAULT_LENGTH, int $type = self::DEFAULT_TYPE): KeyPair
    {
        $errorMessage = 'Can not generate key pair. ';
        $defaultExplanation = 'Try to use another encrypt algorithm, another key type or key length';

        $openssl = openssl_pkey_new([
            'digest_alg' => $algorithm,
            'private_key_bits' => $length,
            'private_key_type' => $type,
        ]);
        if ($openssl === false) {
            if ($error = openssl_error_string() ?: $php_errormsg) {
                $errorMessage .= 'Error: ' . $error;
            } else {
                $errorMessage .= $defaultExplanation;
            }
            throw new KeyGeneratorException($errorMessage, KeyGeneratorException::OPEN_SSL_ERROR_CODE);
        }

        if (!openssl_pkey_export($openssl, $private)) {
            if ($error = openssl_error_string() ?: $php_errormsg) {
                $errorMessage .= 'Error: ' . $error;
            } else {
                $errorMessage .= $defaultExplanation;
            }
            throw new KeyGeneratorException($errorMessage, KeyGeneratorException::OPEN_SSL_ERROR_CODE);
        }

        $public = openssl_pkey_get_details($openssl);
        if ($public === false) {
            if ($error = openssl_error_string() ?: $php_errormsg) {
                $errorMessage .= 'Error: ' . $error;
            } else {
                $errorMessage .= $defaultExplanation;
            }
            throw new KeyGeneratorException($errorMessage, KeyGeneratorException::OPEN_SSL_ERROR_CODE);
        }

        return new KeyPair($private, $public['key']);
    }
}