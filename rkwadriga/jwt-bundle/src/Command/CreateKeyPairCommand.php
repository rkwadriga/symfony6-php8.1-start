<?php declare(strict_types=1);
/**
 * Created 2021-12-04
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Command;

use Rkwadriga\JwtBundle\DependencyInjection\Security\Authenticators\LoginAuthenticator;
use Rkwadriga\JwtBundle\DependencyInjection\Services\Generator;
use Rkwadriga\JwtBundle\DependencyInjection\Services\KeyPair;
use Rkwadriga\JwtBundle\Exceptions\FileSystemException;
use Rkwadriga\JwtBundle\Exceptions\KeyGeneratorException;
use Rkwadriga\JwtBundle\DependencyInjection\Services\FileSystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class CreateKeyPairCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'rkwadriga:generate-keypair';

    public function __construct(
        private FileSystem $fileSystem,
        private Generator $generator,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setName(self::$defaultName)
            ->setDescription('Generate new secret and private keys for encrypting/decrypting JWS')
            ->setHelp('This command allows you to generate new secret and private keys for encrypting/decrypting JWS in specific directory. By default it\'s config/jwt')
            ->addArgument('directory', InputArgument::OPTIONAL, 'The directory for new key pair');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $defaultDirectory = KeyPair::DEFAULT_DIR;
        $question = "Enter directory to hold the key pair (default: {$defaultDirectory}): ";
        $dirName = $this->getFromParamOrAsk($input, $output, 'directory', $question, KeyPair::DEFAULT_DIR);
        $directory = $this->fileSystem->getDirectory($dirName);
        if (!is_writable($directory)) {
            $output->writeln("Directory {$directory} is not writable, check the access rights");
            return Command::FAILURE;
        }

        $privateKey = KeyPair::privateKeyPath($directory);
        $publicKey = KeyPair::publicKeyPath($directory);
        if (file_exists($privateKey) || file_exists($publicKey)) {
            $question = 'There are already key pair exist in target directory. '
                        . 'Do yo wand to rewrite them? (y/n, default y): ';
            $answer = $this->ask($input, $output, $question, 'y');
            if (strtolower($answer) === 'n') {
                return Command::SUCCESS;
            }

            if (!$this->removeFile($output, $privateKey) || !$this->removeFile($output, $publicKey)) {
                 return Command::FAILURE;
            }
        }

        try {
            $keyPair = $this->generator->generate();
        } catch (KeyGeneratorException $e) {
            $output->writeln($e->getMessage());
            return Command::FAILURE;
        }

        if (!$this->writeFile($output, $privateKey, $keyPair->getPrivate())
            || !$this->writeFile($output, $publicKey, $keyPair->getPublic())
        ) {
            return Command::FAILURE;
        }

        $output->writeln('Key pair successfully generated. Check them at the ' . $directory);
        return Command::SUCCESS;
    }

    private function getFromParamOrAsk(InputInterface $input, OutputInterface $output, string $paramName, ?string $question = null, mixed $defaultValue = null): mixed
    {
        if (($result = $input->getArgument($paramName)) === null) {
            $result = $this->ask($input, $output, $question ?: $paramName . ': ', $defaultValue);
        }
        return $result;
    }

    private function ask(InputInterface $input, OutputInterface $output, string $question, mixed $defaultValue = null): mixed
    {
        return $this->getHelper('question')->ask($input, $output, new Question($question, $defaultValue));
    }

    private function removeFile(OutputInterface $output, string $file): bool
    {
        try {
            $this->fileSystem->rmFile($file);
        } catch (FileSystemException $e) {
            $output->writeln($e->getMessage());
            $output->writeln('Try to delete public and private keys by yourself and then run this command again');
            return false;
        }

        return true;
    }

    private function writeFile(OutputInterface $output, string $file, string $data): bool
    {
        try {
            $this->fileSystem->write($file, $data);
        } catch (FileSystemException $e) {
            $output->writeln($e->getMessage());
            return false;
        }

        return true;
    }
}