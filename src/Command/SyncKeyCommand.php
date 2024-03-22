<?php declare(strict_types=1);
/**
 * This file is part of the Webkey Directory project.
 *
 * Licensed under GNU Affero General Public License v3.0
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wkd\Command;

use League\Flysystem\{
    Local\LocalFilesystemAdapter,
    Filesystem
};
use PsrDiscovery\Discover;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\{
    Attribute\AsCommand,
    Command\Command,
    Input\InputInterface,
    Input\InputOption,
    Output\OutputInterface,
    Question\Question,
    Style\SymfonyStyle,
};

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
final class SyncKeyCommand extends Command
{
    const EMAIL_PATTERN   = '/([A-Z0-9._%+-])+@[A-Z0-9.-]+\.[A-Z]{2,}/i';
    const CONTENT_TYPE    = 'application/json; charset=utf-8';
    const HTTP_USER_AGENT = 'Webkey-Directory-Client';
    const REQUEST_METHOD  = 'GET';
    const WKS_URL_OPTION  = 'wks-url';

    private ?string $wksUrl;

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

    public static function extractEmail(string $userId): string
    {
        if (preg_match(self::EMAIL_PATTERN, $userId, $matches)) {
            return $matches[0];
        };
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input, OutputInterface $output
    ): int
    {
        if (!empty($this->wksUrl)) {
            $this->synchronize();
        }
        else {
            return $this->missingParameter($input, $output);
        }
        $output->writeln('Web keys successfully synchronized!');
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->addOption(
            self::WKS_URL_OPTION,
            null,
            InputOption::VALUE_REQUIRED,
            'The webkey service url.'
        );
        $this->setHelp(
            'This command allows you to sync OpenPGP public keys from webkey service.'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(
        InputInterface $input, OutputInterface $output
    )
    {
        $this->wksUrl = $input->getOption(self::WKS_URL_OPTION);

        $helper = $this->getHelper('question');
        if (empty($this->wksUrl)) {
            $this->wksUrl = $helper->ask(
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
    protected function missingParameter(
        InputInterface $input, OutputInterface $output
    ): int
    {
        $style = new SymfonyStyle($input, $output);
        $style->error(sprintf(
            '%s parameter is missing!',
            self::WKS_URL_OPTION,
        ));
        return 1;
    }

    private function synchronize(): void
    {
        $response = $this->sendRequest()->getBody()->getContents();
        if ($directory = json_decode($response, true)) {
            if (!empty($byFingerprints = $directory['fingerprint'] ?? [])) {
                $fs = new Filesystem(
                    new LocalFilesystemAdapter(
                        $this->container->get('vks.storage.fingerprint')
                    )
                );
                foreach ($byFingerprints as $fingerprint => $keyData) {
                    $fs->write(strtolower($fingerprint), $keyData);
                }
            }

            if (!empty($byKeyIDs = $directory['keyid'] ?? [])) {
                $fs = new Filesystem(
                    new LocalFilesystemAdapter(
                        $this->container->get('vks.storage.keyid')
                    )
                );
                foreach ($byKeyIDs as $keyID => $keyData) {
                    $fs->write(strtolower($keyID), $keyData);
                }
            }

            if (!empty($byEmails = $directory['email'] ?? [])) {
                $fs = new Filesystem(
                    new LocalFilesystemAdapter(
                        $this->container->get('vks.storage.email')
                    )
                );
                foreach ($byEmails as $email => $keyData) {
                    $fs->write(strtolower($email), $keyData);
                }
            }

            if (!empty($byDomains = $directory['domain'] ?? [])) {
                $fs = new Filesystem(
                    new LocalFilesystemAdapter(
                        $this->container->get('wkd.storage')
                    )
                );
                foreach ($byDomains as $domain => $wkdHashs) {
                    foreach ($wkdHashs as $hash => $keyData) {
                        $fs->write(
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

    private function sendRequest(): ResponseInterface
    {
        return Discover::httpClient()->sendRequest(
            Discover::httpRequestFactory()
                ->createRequest(self::REQUEST_METHOD, $this->wksUrl)
                ->withHeader('Content-Type', self::CONTENT_TYPE)
                ->withHeader(
                    'User-Agent',
                    $_SERVER['HTTP_USER_AGENT'] ?? self::HTTP_USER_AGENT
                )
        );
    }
}
