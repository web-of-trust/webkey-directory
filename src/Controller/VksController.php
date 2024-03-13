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
use Psr\Container\ContainerInterface;
use Psr\Http\Message\{
    ResponseInterface,
    ServerRequestInterface,
};
use Slim\Views\PhpRenderer;

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
     * Vks controller constructor.
     *
     * @param PhpRenderer $renderer
     * @param ContainerInterface $container
     */
    public function __construct(
        private readonly PhpRenderer $renderer, ContainerInterface $container
    )
    {
        parent::__construct($container);
    }

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
            $storage = $this->getContainer()->get('vks.fingerprint.storage');
            $location = $args['fingerprint'];
        }
        elseif (!empty($args['keyid'])) {
            $storage = $this->getContainer()->get('vks.keyid.storage');
            $location = $args['keyid'];
        }
        elseif (!empty($args['email'])) {
            $storage = $this->getContainer()->get('vks.email.storage');
            $location = $args['email'];
        }
        if (!empty($storage) && !empty($location)) {
            $filesystem = new Filesystem(
                new LocalFilesystemAdapter($storage)
            );
            if ($filesystem->fileExists($location)) {
                $response->getBody()->write(
                    $filesystem->read($location)
                );
                return $response->withHeader(
                    'Content-Type', 'application/pgp-keys'
                )->withHeader(
                    'Content-Disposition', "attachment; filename=$location"
                )->withHeader(
                    'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0'
                )->withHeader(
                    'Pragma', 'no-cache'
                );
            }
        }
        $response->getBody()->write(
            'No key found for ' . $location
        );
        return $response->withStatus(404);
    }
}
