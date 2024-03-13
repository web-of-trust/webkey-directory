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
 * Wkd controller class
 * Web Key Directory (WKD) Interface
 * https://datatracker.ietf.org/doc/draft-koch-openpgp-webkey-service
 * 
 * @package  Wkd
 * @category Controller
 * @author   Nguyen Van Nguyen - nguyennv1981@gmail.com
 */
class WkdController extends BaseController
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
        $domain = $args['domain'] ?? $request->getHeaderLine('Host');
        $hash = $args['hash'] ?? '';
        if (!empty($domain) && !empty($hash)) {
            $filesystem = new Filesystem(
                new LocalFilesystemAdapter(
                    $this->getContainer()->get('wkd.storage')
                )
            );
            $location = implode([
                $domain,
                DIRECTORY_SEPARATOR,
                $hash,
            ]);
            if ($filesystem->fileExists($location)) {
                return $this->download(
                    $response, $hash, $filesystem->read($location)
                );
            }
        }
        $response->getBody()->write(
            'No key found for this email address.'
        );
        return $response->withStatus(404);
    }
}
