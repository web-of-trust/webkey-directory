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
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /**
     * Get psr container
     *
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
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
