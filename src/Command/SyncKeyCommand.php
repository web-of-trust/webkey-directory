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
class SyncKeyCommand extends Command
{
    const EMAIL_PATTERN   = '/([A-Z0-9._%+-])+@[A-Z0-9.-]+\.[A-Z]{2,}/i';
    const CONTENT_TYPE    = 'application/json; charset=utf-8';
    const HTTP_USER_AGENT = 'Webkey-Directory-Client';
    const REQUEST_METHOD  = 'GET';
    const WKS_URL_OPTION  = 'wks-url';

    const SPLIT_PATTERN      = '/^-----[^-]+-----$/';
    const EMPTY_LINE_PATTERN = '/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/';
    const LINE_SPLIT_PATTERN = '/\r\n|\n|\r/';
    const HEADER_PATTERN     = '/^([^\s:]|[^\s:][^:]*[^\s:]): .+$/';

    const PUBLIC_KEY_BLOCK_BEGIN = "-----BEGIN PGP PUBLIC KEY BLOCK-----\n";
    const PUBLIC_KEY_BLOCK_END   = "-----END PGP PUBLIC KEY BLOCK-----\n";

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
        if ($certs = json_decode($response)) {
            $vksEmails = [];
            $wkdDomains = [];

            $fpFs = new Filesystem(
                new LocalFilesystemAdapter(
                    $this->container->get('vks.storage.fingerprint')
                )
            );
            $keyFs = new Filesystem(
                new LocalFilesystemAdapter(
                    $this->container->get('vks.storage.keyid')
                )
            );
            foreach ($certs as $cert) {
                if (empty($wkdDomains[$cert->domain][$cert->wkd_hash])) {
                    $wkdDomains[$cert->domain][$cert->wkd_hash] = self::decodeArmored($cert->key_data);
                }
                else {
                    $wkdDomains[$cert->domain][$cert->wkd_hash] .= self::decodeArmored($cert->key_data);
                }

                if ($email = self::extractEmail($cert->primary_user)) {
                    if (empty($vksEmails[$email])) {
                        $vksEmails[$email] = self::decodeArmored($cert->key_data);
                    }
                    else {
                        $vksEmails[$email] .= self::decodeArmored($cert->key_data);
                    }
                }

                $fpFs->write(
                    strtolower($cert->fingerprint),
                    $cert->key_data
                );
                $keyFs->write(
                    strtolower($cert->key_id),
                    $cert->key_data
                );
            }

            if (!empty($vksEmails)) {
                $vksFs = new Filesystem(
                    new LocalFilesystemAdapter(
                        $this->container->get('vks.storage.email')
                    )
                );
                foreach ($vksEmails as $email => $keyData) {
                    $vksFs->write(
                        $email,
                        self::encodeArmor($keyData)
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
                            self::encodeArmor($keyData)
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
            ->createRequest(self::REQUEST_METHOD, $this->wksUrl)
            ->withHeader('Content-Type', self::CONTENT_TYPE)
            ->withHeader(
                'User-Agent',
                $_SERVER['HTTP_USER_AGENT'] ?? self::HTTP_USER_AGENT
            );
        return $httpClient->sendRequest($httpRequest);
    }

    private static function encodeArmor(string $data): string
    {
        return implode([
            self::PUBLIC_KEY_BLOCK_BEGIN . "\n",
            chunk_split(base64_encode($data), 76, "\n"),
            '=' . self::crc24Checksum($data) . "\n",
            self::PUBLIC_KEY_BLOCK_END,
        ]);
    }

    private static function decodeArmored(string $armored): string
    {
        $textDone = false;
        $checksum = '';
        $type = null;
        $dataLines = [];

        $lines = preg_split(self::LINE_SPLIT_PATTERN, $armored);
        if (!empty($lines)) {
            foreach ($lines as $line) {
                if ($type === null && preg_match(self::SPLIT_PATTERN, $line)) {
                    $type = $line;
                }
                else {
                    if (preg_match(self::HEADER_PATTERN, $line)) {
                        continue;
                    }
                    elseif (!$textDone && preg_match('/SIGNED MESSAGE/', $type)) {
                        if (!preg_match(self::SPLIT_PATTERN, $line)) {
                            continue;
                        }
                        else {
                            $textDone = true;
                        }
                    }
                    elseif (!preg_match(self::SPLIT_PATTERN, $line)) {
                        if (preg_match(self::EMPTY_LINE_PATTERN, $line)) {
                            continue;
                        }
                        if (strpos($line, '=') === 0) {
                            $checksum = substr($line, 1);
                        }
                        else {
                            $dataLines[] = $line;
                        }
                    }
                }
            }
        }

        $data = base64_decode(implode($dataLines));
        if (!empty($checksum) && ($checksum != self::crc24Checksum($data))) {
            throw new \UnexpectedValueException(
                'Ascii armor integrity check failed'
            );
        }
        return preg_match('/PUBLIC KEY BLOCK/', $type) ? $data : '';
    }

    private static function crc24Checksum(string $data): string
    {
        $crc = 0xb704ce;
        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $crc ^= (ord($data[$i]) & 255) << 16;
            for ($j = 0; $j < 8; $j++) {
                $crc <<= 1;
                if ($crc & 0x1000000) {
                    $crc ^= 0x1864cfb;
                }
            }
        }
        return base64_encode(
            substr(pack('N', $crc & 0xffffff), 1)
        );
    }
}
