<?php declare(strict_types=1);
/**
 * Created 2021-12-04
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Helpers;

class FileSystemHelper
{
    public static function normalizePath(string $path): string
    {
        if (is_dir($path) ||file_exists($path)) {
            return $path;
        }

        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
        if (substr($path, 0, 1) === DIRECTORY_SEPARATOR) {
            $path = substr($path, 1);
        }
        if (substr($path, -1) === DIRECTORY_SEPARATOR) {
            $path = substr($path, 0, -1);
        }

        return $path;
    }
}