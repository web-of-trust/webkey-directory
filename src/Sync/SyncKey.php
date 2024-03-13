<?php declare(strict_types=1);
/**
 * This file is part of the Webkey Directory project.
 *
 * Licensed under GNU Affero General Public License v3.0
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wkd\Sync;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PsrDiscovery\Discover;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\{
    RequestFactoryInterface,
    ResponseInterface,
};

/**
 * Sync key class
 * 
 * @package  Wkd
 * @category Sync
 * @author   Nguyen Van Nguyen - nguyennv1981@gmail.com
 */
final class SyncKey
{
    const EMAIL_PATTERN   = '/([A-Z0-9._%+-])+@[A-Z0-9.-]+\.[A-Z]{2,}/i';
    const CONTENT_TYPE    = 'application/json; charset=utf-8';
    const HTTP_USER_AGENT = 'Webkey-Directory-Client';
    const REQUEST_METHOD  = 'GET';

    private readonly ClientInterface $httpClient;
    private readonly RequestFactoryInterface $requestFactory;

    /**
     * Sync key constructor.
     *
     * @param string $webkeyServiceUrl
     * @param ContainerInterface $container
     */
    public function __construct(
        private readonly string $webkeyServiceUrl,
        private readonly ContainerInterface $container
    )
    {
        $this->httpClient = Discover::httpClient();
        $this->requestFactory = Discover::httpRequestFactory();
    }

    public function sync(): void
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

    private function sendRequest(): ResponseInterface
    {
        $httpRequest = $this->requestFactory
            ->createRequest(self::REQUEST_METHOD, $this->webkeyServiceUrl)
            ->withHeader('Content-Type', self::CONTENT_TYPE)
            ->withHeader('User-Agent', $_SERVER['HTTP_USER_AGENT'] ?? self::HTTP_USER_AGENT);
        return $this->httpClient->sendRequest($httpRequest);
    }

    private static function extractEmail(string $userId): string
    {
        if (preg_match(self::EMAIL_PATTERN, $userId, $matches)) {
            return $matches[0];
        };
        return '';
    }
}
