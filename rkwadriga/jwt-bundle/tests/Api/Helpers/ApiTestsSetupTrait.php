<?php declare(strict_types=1);
/**
 * Created 2021-12-04
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Api\Helpers;

use Doctrine\ORM\EntityManagerInterface;
use Rkwadriga\JwtBundle\DependencyInjection\Security\Authenticators\LoginAuthenticator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

trait ApiTestsSetupTrait
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private PasswordHasherFactoryInterface $encoder;
    private RouterInterface $router;

    private string $loginUrl;
    private string $loginParam;
    private string $passwordParam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->em = $container->get('doctrine')->getManager();
        $this->encoder = $container->get(PasswordHasherFactoryInterface::class);
        $this->router = $container->get(RouterInterface::class);
        $this->loginUrl = $container->getParameter('rkwadriga.jwt._login_url');
        $this->loginParam = $container->getParameter('rkwadriga.jwt.login_pram');
        $this->passwordParam = $container->getParameter('rkwadriga.jwt.password_param');
    }
}