<?php declare(strict_types=1);
/**
 * This file is part of the Webkey Directory project.
 *
 * Licensed under GNU Affero General Public License v3.0
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wkd\Application;

use Slim\App;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Routing\RouteCollectorProxy;
use Wkd\Controller;

/**
 * Route definitions class
 * 
 * @package  Wkd
 * @category Application
 * @author   Nguyen Van Nguyen - nguyennv1981@gmail.com
 */
final class RouteDefinitions
{
    /**
     * Add routes to slim application.
     *
     * @param App $app.
     * @see https://www.slimframework.com/docs/v4/objects/routing.html
     */
    public function __invoke(App $app): void
    {
        $app->getRouteCollector()
            ->setDefaultInvocationStrategy(new RequestResponse());

        $app->get(
            '/', Controller\HomeController::class
        );

        $app->get(
            '/search', Controller\SearchController::class
        );

        $app->get(
            '/.well-known/openpgpkey/{domain}/hu/{hash}',
            Controller\WkdController::class
        );

        $app->get('/.well-known/openpgpkey/{domain}/policy', function ($request, $response, array $args) {
            return $response->withStatus(200);
        });

        $app->group('/vks/v1', static function (RouteCollectorProxy $group) {
            $group->get(
                '/by-fingerprint/{fingerprint}', Controller\VksController::class
            );
            $group->get(
                '/by-keyid/{keyid}', Controller\VksController::class
            );
            $group->get(
                '/by-email/{email}', Controller\VksController::class
            );
        });

        $app->get(
            '/pks/lookup',
            Controller\HkpController::class
        );
    }
}
