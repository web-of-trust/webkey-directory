<?php declare(strict_types=1);
/**
 * This file is part of the Webkey Directory project.
 *
 * Licensed under GNU Affero General Public License v3.0
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wkd\Controller;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\{
    ResponseInterface,
    ServerRequestInterface,
};

/**
 * Abstract base controller class
 * 
 * @package  Wkd
 * @category Controller
 * @author   Nguyen Van Nguyen - nguyennv1981@gmail.com
 */
abstract class BaseController
{
    /**
     * Base controller constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(
        protected readonly ContainerInterface $container
    )
    {
    }

    /**
     * Perform action on controller by calling `self::action`.
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args = []
    ): ResponseInterface
    {
        return $this->action($request, $response, $args);
    }

    /**
     * Perform download content on controller
     * 
     * @param ResponseInterface $response
     * @param string $filename
     * @param string $content
     * @return ResponseInterface
     */
    protected function download(
        ResponseInterface $response,
        string $filename,
        string $content
    ): ResponseInterface
    {
        $ext = $this->container->get('key.extension');
        $response->getBody()->write($content);
        return $response->withHeader(
            'Content-Type', 'application/pgp-keys'
        )->withHeader(
            'Content-Disposition', "attachment; filename=$filename$ext"
        )->withHeader(
            'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0'
        )->withHeader(
            'Pragma', 'no-cache'
        );
    }

    /**
     * Perform action on controller
     * 
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    abstract protected function action(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface;
}
