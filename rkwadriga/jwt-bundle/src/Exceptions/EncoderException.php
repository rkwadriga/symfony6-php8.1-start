<?php declare(strict_types=1);
/**
 * Created 2021-12-05
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Exceptions;

class EncoderException extends EncryptionException
{
    public const PRIVATE_KEY_NOF_FOUND  = 3863947157;
    public const ENCRYPTION_ERROR       = 3896210405;
    public const DECRYPTION_ERROR       = 7006892564;
}