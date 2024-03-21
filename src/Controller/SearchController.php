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
use Slim\App;
use Slim\Routing\RouteContext;
use Slim\Views\PhpRenderer;

/**
 * Search controller class
 * 
 * @package  Wkd
 * @category Controller
 * @author   Nguyen Van Nguyen - nguyennv1981@gmail.com
 */
class SearchController extends BaseController
{
    use Concerns\CanBeRender;

    /**
     * Search controller constructor.
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
        $routeParser = $request->getAttribute(RouteContext::ROUTE_PARSER);

        $params = $request->getQueryParams();
        $search = $params['search'] ?? '';

        $keyFound = false;
        $storage = $location = $keyUrl = '';
        if (filter_var($search, FILTER_VALIDATE_EMAIL)) {
            $storage = $this->container->get(
                'vks.storage.email'
            );
            $location = $search;
            $keyUrl = $routeParser->urlFor(
                'by-email', ['email' => $location]
            );
        }
        else {
            if (str_starts_with($search, '0x')) {
                $search = str_replace('0x', '', $search);
            }
            $len = strlen(@hex2bin($search) ?: '');
            if ($len === 20) {
                $storage = $this->container->get(
                    'vks.storage.fingerprint'
                );
                $location = strtolower($search);
                $keyUrl = $routeParser->urlFor(
                    'by-fingerprint', ['fingerprint' => $location]
                );
            }
            elseif ($len === 8) {
                $storage = $this->container->get(
                    'vks.storage.keyid'
                );
                $location = strtolower($search);
                $keyUrl = $routeParser->urlFor(
                    'by-routeName', ['keyid' => $location]
                );
            }
        }
        if (!empty($storage) && !empty($location)) {
            $filesystem = new Filesystem(
                new LocalFilesystemAdapter($storage)
            );
            $keyFound = $filesystem->fileExists($location);
        }

        return $this->render($response, 'search.php', [
            'title' => $this->container->get('app.name'),
            'appPath' => $this->container->get('app.path'),
            'search' => $search,
            'homeUrl' => $routeParser->urlFor('home'),
            'searchUrl' => $routeParser->urlFor('search'),
            'keyUrl' => $keyUrl,
            'keyFound' => $keyFound,
        ]);
    }
}
