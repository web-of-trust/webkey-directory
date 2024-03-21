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
use Slim\App;
use Slim\Views\PhpRenderer;

/**
 * Home controller class
 * 
 * @package  Wkd
 * @category Controller
 * @author   Nguyen Van Nguyen - nguyennv1981@gmail.com
 */
class HomeController extends BaseController
{
    use Concerns\CanBeRender;

    /**
     * Home controller constructor.
     *
     * @param PhpRenderer $renderer
     * @param ContainerInterface $container
     */
    public function __construct(
        private readonly PhpRenderer $renderer,
        ContainerInterface $container
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
        $app = $this->container->get(App::class);
        $routeParser = $app->getRouteCollector()->getRouteParser();
        return $this->render($response, 'index.php', [
            'title' => $this->container->get('app.name'),
            'basePath' => $app->getBasePath(),
            'homeUrl' => $routeParser->urlFor('home'),
            'searchUrl' => $routeParser->urlFor('search'),
        ]);
    }
}
