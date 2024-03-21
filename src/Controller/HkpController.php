<?php declare(strict_types=1);
/**
 * This file is part of the Webkey Directory project.
 *
 * Licensed under GNU Affero General Public License v3.0
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wkd\Controller;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Psr\Http\Message\{
    ResponseInterface,
    ServerRequestInterface,
};

/**
 * Hkp controller class
 * HTTP Keyserver Protocol (HKP) Interface
 * https://datatracker.ietf.org/doc/html/draft-gallagher-openpgp-hkp
 * 
 * @package  App
 * @category Controller
 * @author   Nguyen Van Nguyen - nguyennv1981@gmail.com
 */
class HkpController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    protected function action(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface
    {
        $params = $request->getQueryParams();
        $op = $params['op'] ?? 'get';
        $search = $params['search'] ?? '';

        $storage = $location = '';
        if (filter_var($search, FILTER_VALIDATE_EMAIL)) {
            $storage = $this->getContainer()->get('vks.storage.email');
            $location = $search;
        }
        else {
            if (str_starts_with($search, '0x')) {
                $search = str_replace('0x', '', $search);
            }
            $len = strlen(@hex2bin($search) ?: '');
            if ($len === 20) {
                $storage = $this->getContainer()->get(
                    'vks.storage.fingerprint'
                );
                $location = strtolower($search);
            }
            elseif ($len === 8) {
                $storage = $this->getContainer()->get(
                    'vks.storage.keyid'
                );
                $location = strtolower($search);
            }
        }

        if (!empty($storage) && !empty($location)) {
            $filesystem = new Filesystem(
                new LocalFilesystemAdapter($storage)
            );
            if ($filesystem->fileExists($location)) {
                if ($op === 'get') {
                    return $this->download(
                        $response, $location, $filesystem->read($location)
                    );
                }
                else {
                    $response->getBody()->write(
                        $op . ' operation not implemented'
                    );

                    return $response;
                }
            }
        }

        $response->getBody()->write(
            'No key found for ' . $search
        );
        return $response->withStatus(404);
    }
}
