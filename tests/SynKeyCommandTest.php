<?php

namespace Wkd\Tests;

use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\Response;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Wkd\Command\SyncKeyCommand;

class SynKeyCommandTest extends TestCase
{
    private $console;

    protected function setUp(): void
    {
        parent::setUp();
        $this->console = $this->runner->getContainer()->get(Application::class);
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

        $command = $this->console->find('webkey:sync');
        $tester = new CommandTester($command);
        $tester->execute([
            '--wks-url' => $url,
        ]);
        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString(
            'Web keys successfully synchronized!', $tester->getDisplay()
        );

        $certs = json_decode(file_get_contents($url));
        $fpStorage = $this->runner->getContainer()->get('vks.fingerprint.storage');
        $keyidStorage = $this->runner->getContainer()->get('vks.keyid.storage');
        $emailStorage = $this->runner->getContainer()->get('vks.email.storage');
        $wkdStorage = $this->runner->getContainer()->get('wkd.storage');
        foreach ($certs as $cert) {
            $this->assertTrue(
                file_exists(implode([
                    $fpStorage,
                    DIRECTORY_SEPARATOR,
                    strtoupper($cert->fingerprint),
                ]))
            );
            $this->assertTrue(
                file_exists(implode([
                    $keyidStorage,
                    DIRECTORY_SEPARATOR,
                    strtoupper($cert->key_id),
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
