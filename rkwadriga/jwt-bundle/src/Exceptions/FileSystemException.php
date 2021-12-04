<?php declare(strict_types=1);
/**
 * Created 2021-12-04
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Exceptions;

class FileSystemException extends BaseException
{
    public const DIRECTORY_NOT_READABLE = 8731594175;
    public const DIRECTORY_NOT_WRITABLE = 8632897328;
    public const FILE_NOT_READABLE =      9682052005;
    public const FILE_NOT_WRITABLE =      9001247753;
    public const DIRECTORY_NOT_FOUND =    3699876012;
    public const FILE_NOT_FOUND =         9875625689;
    public const WRITING_ERROR =          9632578965;
    public const DELETING_ERROR =         3258014755;
}