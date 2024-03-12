<?php declare(strict_types=1);
/**
 * This file is part of the Webkey Directory project.
 *
 * Licensed under GNU Affero General Public License v3.0
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wkd\Controller;

use Psr\Http\Message\{
    ResponseInterface,
    ServerRequestInterface,
};
use Slim\Views\PhpRenderer;

/**
 * Search controller class
 * 
 * @package  Wkd
 * @category Controller
 * @author   Nguyen Van Nguyen - nguyennv1981@gmail.com
 */
class HomeController extends BaseController
{
    use Concerns\CanBeRender;

    /**
     * Search controller constructor.
     *
     * @param PhpRenderer $renderer
     */
    public function __construct(private readonly PhpRenderer $renderer)
    {
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
        return $response;
    }
}
