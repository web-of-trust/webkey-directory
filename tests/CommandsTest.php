<?php

namespace Wkd\Tests;

use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Wkd\Application\ConsoleRunner;
use Wkd\Command\SyncKeyCommand;

class CommandsTest extends TestCase
{
    private $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = (new ConsoleRunner(dirname(__DIR__)))->getContainer();
    }

    public function testSynKey()
    {
        $server = new MockWebServer;
        $server->start();

        $url = $server->setResponseOfPath(
            '/certificate.json',
            new Response(
                file_get_contents('tests/data/certificate.json'),
                [
                    'Content-Type' => 'application/json',
                    'Cache-Control' => 'no-cache',
                ],
                200
            )
        );

        $console = $this->container->get(Application::class);
        $command = $console->find('webkey:sync');
        $tester = new CommandTester($command);
        $tester->execute([
            '--wks-url' => $url,
        ]);
        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString(
            'Web keys successfully synchronized!', $tester->getDisplay()
        );

        $certs = json_decode(file_get_contents($url));
        $fpStorage = $this->container->get('vks.storage.fingerprint');
        $keyidStorage = $this->container->get('vks.storage.keyid');
        $emailStorage = $this->container->get('vks.storage.email');
        $wkdStorage = $this->container->get('wkd.storage');
        foreach ($certs as $cert) {
            $this->assertTrue(
                file_exists(implode([
                    $fpStorage,
                    DIRECTORY_SEPARATOR,
                    strtolower($cert->fingerprint),
                ]))
            );
            $this->assertTrue(
                file_exists(implode([
                    $keyidStorage,
                    DIRECTORY_SEPARATOR,
                    strtolower($cert->key_id),
                ]))
            );
            $this->assertTrue(
                file_exists(implode([
                    $wkdStorage,
                    DIRECTORY_SEPARATOR,
                    $cert->domain,
                    DIRECTORY_SEPARATOR,
                    $cert->wkd_hash,
                ]))
            );

            $email = SyncKeyCommand::extractEmail($cert->primary_user);
            $this->assertTrue(
                file_exists(implode([
                    $emailStorage,
                    DIRECTORY_SEPARATOR,
                    $email,
                ]))
            );
        }

        $server->stop();
    }
}
