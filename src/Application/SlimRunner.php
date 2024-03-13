<?php declare(strict_types=1);
/**
 * This file is part of the Webkey Directory project.
 *
 * Licensed under GNU Affero General Public License v3.0
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wkd\Application;

use DI\Bridge\Slim\Bridge;
use PsrDiscovery\Discover;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Factory\AppFactory;

/**
 * Slim runner class
 * Run the Slim application.
 *
 * @package  Wkd
 * @category Application
 * @author   Nguyen Van Nguyen - nguyennv1981@gmail.com
 */
final class SlimRunner extends AbstractRunner
{
    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        AppFactory::setResponseFactory(Discover::httpResponseFactory());
        AppFactory::setStreamFactory(Discover::httpStreamFactory());

        $app = Bridge::create($this->getContainer());
        self::registerMiddlewares($app);
        self::registerRoutes($app);

        $app->run();
    }

    /**
     * Register middlewares
     * 
     * @param App $app
     * @return void
     */
    private static function registerMiddlewares(App $app): void
    {
        $container = $app->getContainer();
        $app->addRoutingMiddleware();
        $app->addBodyParsingMiddleware();
        $app->addErrorMiddleware(
            displayErrorDetails: (bool) $container->get('error.display'),
            logErrors: (bool) $container->get('error.log'),
            logErrorDetails: (bool) $container->get('error.details'),
            logger: $container->get(LoggerInterface::class),
        );
    }

    /**
     * Register routes
     * 
     * @param App $app
     * @return void
     */
    private static function registerRoutes(App $app): void
    {
        (new RouteDefinitions())($app);
    }
}
