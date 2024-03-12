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

    const VKS_FINGERPRINT_STORE = 'vks' . DIRECTORY_SEPARATOR . 'fingerprint';
    const VKS_KEYID_STORE       = 'vks' . DIRECTORY_SEPARATOR . 'keyid';
    const VKS_EMAIL_STORE       = 'vks' . DIRECTORY_SEPARATOR . 'email';
    const WKD_STORE             = 'wkd';

    private readonly ClientInterface $httpClient;
    private readonly RequestFactoryInterface $requestFactory;
    private readonly Filesystem $filesystem;

    public function __construct(
        private readonly string $webkeyServiceUrl,
        string $keyStoreDirectory
    )
    {
        $this->httpClient = Discover::httpClient();
        $this->requestFactory = Discover::httpRequestFactory();
        $this->filesystem = new Filesystem(
            new LocalFilesystemAdapter($keyStoreDirectory)
        );
    }

    public function sync(): void
    {
        $response = $this->sendRequest()->getBody()->getContents();
        if ($certificates = json_decode($response)) {
            $vksEmails = [];
            $wkdDomains = [];
            foreach ($certificates as $certificate) {
                if (empty($wkdDomains[$certificate->domain][$certificate->wkd_hash])) {
                    $wkdDomains[$certificate->domain][$certificate->wkd_hash] = $certificate->key_data;
                }
                else {
                    $wkdDomains[$certificate->domain][$certificate->wkd_hash] .= $certificate->key_data;
                }

                if ($email = self::extractEmail($certificate->primary_user)) {
                    if (empty($vksEmails[$email])) {
                        $vksEmails[$email] = $certificate->key_data;
                    }
                    else {
                        $vksEmails[$email] .= $certificate->key_data;
                    }
                }

                $this->filesystem->write(
                    implode([
                        self::VKS_FINGERPRINT_STORE,
                        DIRECTORY_SEPARATOR,
                        strtoupper($certificate->fingerprint),
                    ]),
                    $certificate->key_data
                );
                $this->filesystem->write(
                    implode([
                        self::VKS_KEYID_STORE,
                        DIRECTORY_SEPARATOR,
                        strtoupper($certificate->key_id),
                    ]),
                    $certificate->key_data
                );
            }
            foreach ($vksEmails as $email => $keyData) {
                $this->filesystem->write(
                    implode([
                        self::VKS_EMAIL_STORE,
                        DIRECTORY_SEPARATOR,
                        $email,
                    ]),
                    $keyData
                );
            }

            foreach ($wkdDomains as $domain => $wkdHashs) {
                foreach ($wkdHashs as $hash => $keyData) {
                    $this->filesystem->write(
                        implode([
                            self::WKD_STORE,
                            DIRECTORY_SEPARATOR,
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
