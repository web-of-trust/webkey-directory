<?php declare(strict_types=1);
/**
 * This file is part of the Webkey Directory project.
 *
 * Licensed under GNU Affero General Public License v3.0
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wkd\Command;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PsrDiscovery\Discover;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Sync key command class
 * 
 * @package  Wkd
 * @category Command
 * @author   Nguyen Van Nguyen - nguyennv1981@gmail.com
 */
#[AsCommand(
    name: 'webkey:sync',
    description: 'Sync OpenPGP public keys from webkey service.'
)]
class SyncKeyCommand extends Command
{
    const EMAIL_PATTERN   = '/([A-Z0-9._%+-])+@[A-Z0-9.-]+\.[A-Z]{2,}/i';
    const CONTENT_TYPE    = 'application/json; charset=utf-8';
    const HTTP_USER_AGENT = 'Webkey-Directory-Client';
    const REQUEST_METHOD  = 'GET';

    const WEBKEY_SERVICE_URL_OPTION  = 'webkey-service-url';

    private ?string $webkeyServiceUrl;

    /**
     * Sync key command constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(
        private readonly ContainerInterface $container
    )
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!empty($this->webkeyServiceUrl)) {
            $this->syncKey();
        }
        else {
            return $this->missingParameter($input, $output);
        }
        $output->writeln('Web keys successfully synced!');
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->addOption(
            self::WEBKEY_SERVICE_URL_OPTION, null, InputOption::VALUE_REQUIRED, 'The webkey service url.'
        );
        $this->setHelp('This command allows you to sync OpenPGP public keys from webkey service.');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->webkeyServiceUrl = $input->getOption(self::WEBKEY_SERVICE_URL_OPTION);

        $helper = $this->getHelper('question');
        if (empty($this->webkeyServiceUrl)) {
            $this->webkeyServiceUrl = $helper->ask(
                $input,
                $output,
                new Question('Please enter the webkey service url: '),
            );
        }
    }

    /**
     * Output missing parameter.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function missingParameter(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $style->error(sprintf(
            '%s parameter is missing!',
            self::WEBKEY_SERVICE_URL_OPTION,
        ));
        return 1;
    }

    private function syncKey(): void
    {
        $response = $this->sendRequest()->getBody()->getContents();
        if ($certs = json_decode($response)) {
            $vksEmails = [];
            $wkdDomains = [];

            $fpFs = new Filesystem(
                new LocalFilesystemAdapter(
                    $this->container->get('vks.fingerprint.storage')
                )
            );
            $keyFs = new Filesystem(
                new LocalFilesystemAdapter(
                    $this->container->get('vks.keyid.storage')
                )
            );
            foreach ($certs as $cert) {
                if (empty($wkdDomains[$cert->domain][$cert->wkd_hash])) {
                    $wkdDomains[$cert->domain][$cert->wkd_hash] = $cert->key_data;
                }
                else {
                    $wkdDomains[$cert->domain][$cert->wkd_hash] .= $cert->key_data;
                }

                if ($email = self::extractEmail($cert->primary_user)) {
                    if (empty($vksEmails[$email])) {
                        $vksEmails[$email] = $cert->key_data;
                    }
                    else {
                        $vksEmails[$email] .= $cert->key_data;
                    }
                }

                $fpFs->write(
                    strtoupper($cert->fingerprint),
                    $cert->key_data
                );
                $keyFs->write(
                    strtoupper($cert->key_id),
                    $cert->key_data
                );
            }

            if (!empty($vksEmails)) {
                $vksFs = new Filesystem(
                    new LocalFilesystemAdapter(
                        $this->container->get('vks.email.storage')
                    )
                );
                foreach ($vksEmails as $email => $keyData) {
                    $vksFs->write(
                        $email,
                        $keyData
                    );
                }
            }

            if (!empty($wkdDomains)) {
                $wkdFs = new Filesystem(
                    new LocalFilesystemAdapter(
                        $this->container->get('wkd.storage')
                    )
                );
                foreach ($wkdDomains as $domain => $wkdHashs) {
                    foreach ($wkdHashs as $hash => $keyData) {
                        $wkdFs->write(
                            implode([
                                $domain,
                                DIRECTORY_SEPARATOR,
                                $hash,
                            ]),
                            $keyData
                        );
                    }
                }
            }
        }
    }

    private function sendRequest()
    {
        $httpClient = Discover::httpClient();
        $requestFactory = Discover::httpRequestFactory();
        $httpRequest = $requestFactory
            ->createRequest(self::REQUEST_METHOD, $this->webkeyServiceUrl)
            ->withHeader('Content-Type', self::CONTENT_TYPE)
            ->withHeader('User-Agent', $_SERVER['HTTP_USER_AGENT'] ?? self::HTTP_USER_AGENT);
        return $httpClient->sendRequest($httpRequest);
    }

    private static function extractEmail(string $userId): string
    {
        if (preg_match(self::EMAIL_PATTERN, $userId, $matches)) {
            return $matches[0];
        };
        return '';
    }
}
