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
            '/directory.json',
            new Response(
                file_get_contents('tests/data/directory.json'),
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

        if ($directory = json_decode(file_get_contents($url), true)) {
            if (!empty($byFingerprints = $directory['fingerprint'] ?? [])) {
                $storage = $this->container->get('vks.storage.fingerprint');
                foreach ($byFingerprints as $fingerprint => $keyData) {
                    $this->assertTrue(
                        file_exists(implode([
                            $storage,
                            DIRECTORY_SEPARATOR,
                            strtolower($fingerprint),
                        ]))
                    );
                }
            }

            if (!empty($byKeyIDs = $directory['keyid'] ?? [])) {
                $storage = $this->container->get('vks.storage.keyid');
                foreach ($byKeyIDs as $keyID => $keyData) {
                    $this->assertTrue(
                        file_exists(implode([
                            $storage,
                            DIRECTORY_SEPARATOR,
                            strtolower($keyID),
                        ]))
                    );
                }
            }

            if (!empty($byEmails = $directory['email'] ?? [])) {
                $storage = $this->container->get('vks.storage.email');
                foreach ($byEmails as $email => $keyData) {
                    $this->assertTrue(
                        file_exists(implode([
                            $storage,
                            DIRECTORY_SEPARATOR,
                            $email,
                        ]))
                    );
                }
            }

            if (!empty($byDomains = $directory['domain'] ?? [])) {
                $storage = $this->container->get('wkd.storage');
                foreach ($byDomains as $domain => $wkdHashs) {
                    foreach ($wkdHashs as $hash => $keyData) {
                        $this->assertTrue(
                            file_exists(implode([
                                $storage,
                                DIRECTORY_SEPARATOR,
                                $domain,
                                DIRECTORY_SEPARATOR,
                                $hash,
                            ]))
                        );
                    }
                }
            }
        }

        $server->stop();
    }
}
