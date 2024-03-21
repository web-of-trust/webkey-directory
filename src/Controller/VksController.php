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
 * Vks controller class
 * Verifying Keyserver (VKS) Interface
 * 
 * @package  Wkd
 * @category Controller
 * @author   Nguyen Van Nguyen - nguyennv1981@gmail.com
 */
class VksController extends BaseController
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
        $storage = $location = '';
        if (!empty($args['fingerprint'])) {
            $storage = $this->getContainer()->get(
                'vks.storage.fingerprint'
            );
            $location = strtolower($args['fingerprint']);
        }
        elseif (!empty($args['keyid'])) {
            $storage = $this->getContainer()->get(
                'vks.storage.keyid'
            );
            $location = strtolower($args['keyid']);
        }
        elseif (!empty($args['email'])) {
            $storage = $this->getContainer()->get(
                'vks.storage.email'
            );
            $location = $args['email'];
        }
        if (!empty($storage) && !empty($location)) {
            $filesystem = new Filesystem(
                new LocalFilesystemAdapter($storage)
            );
            if ($filesystem->fileExists($location)) {
                return $this->download(
                    $response, $location, $filesystem->read($location)
                );
            }
        }
        $response->getBody()->write(
            'No key found for ' . $location
        );
        return $response->withStatus(404);
    }
}
