<?php declare(strict_types=1);
/**
 * Created 2021-12-04
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Services;

use Rkwadriga\JwtBundle\Exceptions\FileSystemException;
use Rkwadriga\JwtBundle\Helpers\FileSystemHelper;
use Symfony\Component\HttpKernel\KernelInterface;

class FileSystem
{
    private const TYPE_FILE = 'file';
    private const TYPE_DIR = 'dir';


    public function __construct(
        private string $baseDIr
    ) {}

    public function write(string $file, string $data, bool $autoCreate = true): string
    {
        if (!$autoCreate && !file_exists($file)) {
            throw new FileSystemException("File {$file} does not exist", FileSystemException::FILE_NOT_FOUND);
        }

        $file = $this->getPath($file);
        $dir = dirname($file);
        if (!is_writable($dir)) {
            throw new FileSystemException("Directory {$dir} is not writable", FileSystemException::DIRECTORY_NOT_WRITABLE);
        }
        if (file_exists($file) && !is_writable($file)) {
            throw new FileSystemException("File {$file} is not writable", FileSystemException::FILE_NOT_WRITABLE);
        }

        if (!file_put_contents($file, $data)) {
            $message = "Can not write the file {$file}. ";
            if ($php_errormsg) {
                $message .= "Error: {$message}";
            } else {
                $message .= 'Check the writing access to the directory' . dirname($file);
            }
            throw new FileSystemException($message, FileSystemException::WRITING_ERROR);
        }

        return $file;
    }

    public function getPath(string $path, bool $autoCrete = true): string
    {
        if (file_exists($path) || is_dir($path)) {
            return $path;
        }

        $dirPath = dirname($path);
        return $this->getDirectory($dirPath, $autoCrete) . DIRECTORY_SEPARATOR . basename($path);
    }

    public function getDirectory(string &$path, bool $autoCrete = true): string
    {
        if (is_dir($path)) {
            return $path;
        }

        $dir = $this->baseDIr;
        $path = FileSystemHelper::normalizePath($path);

        foreach (explode(DIRECTORY_SEPARATOR, $path) as $subDir) {
            $dir .= DIRECTORY_SEPARATOR . $subDir;
            if ($autoCrete && !is_dir($dir) && !mkdir($dir)) {
                $message = "Can not create dir \"{$dir}\"";
                if ($php_errormsg) {
                    $message .= " (Error: {$php_errormsg})";
                } else {
                    $parentDirectory = dirname($dir);
                    $fullDir = $this->baseDIr . DIRECTORY_SEPARATOR . $path;
                    $message .= ". Check the access rights to the dir {$parentDirectory} "
                        . "or create the dir ({$fullDir}) by yourself";
                }
                throw new FileSystemException($message, FileSystemException::DIRECTORY_NOT_WRITABLE);
            }
        }

        return $dir;
    }

    public function rmFile(string $file): void
    {
        $this->rm($this->getPath($file, false), self::TYPE_FILE);
    }

    public function rmDir(string $dir): void
    {
        $this->rm($this->getDirectory($dir, false), self::TYPE_DIR);
    }

    private function rm(string $path, string $type): void
    {
        if (!file_exists($path) && !is_dir($path)) {
            return;
        }

        if (!is_writable($path)) {
            $code = $type === self::TYPE_FILE ? FileSystemException::FILE_NOT_WRITABLE : FileSystemException::DIRECTORY_NOT_WRITABLE;
            throw new FileSystemException("Can non delete the {$type} {$path}: access denied", $code);
        }

        if (!unlink($path)) {
            $message = "Can non delete the {$type} {$path}";
            if ($php_errormsg) {
                $message .= " (Error: {$php_errormsg})";
            } else {
                $parentDirectory = dirname($path);
                $message .= ". Try to check the access rights to the directory {$parentDirectory} and the {$type}";
            }
            throw new FileSystemException($message, FileSystemException::DELETING_ERROR);
        }
    }
}