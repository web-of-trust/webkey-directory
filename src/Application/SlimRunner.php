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
use DI\ContainerBuilder;
use PsrDiscovery\Discover;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use Slim\Logger;


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

        $app = Bridge::create($this->container);
        $app->setBasePath($this->container->get('app.path'));
        $app->addRoutingMiddleware();
        $app->addErrorMiddleware(
            displayErrorDetails: (bool) $this->container->get('error.display.details'),
            logErrors: (bool) $this->container->get('error.log'),
            logErrorDetails: (bool) $this->container->get('error.log.details'),
            logger: $this->container->get(LoggerInterface::class),
        );
        (new RouteDefinitions())($app);

        $app->run();
    }

    /**
     * {@inheritdoc}
     */
    protected function serviceDefinitions(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([
            LoggerInterface::class => fn () => Discover::log() ?? new Logger(),
            PhpRenderer::class => fn (ContainerInterface $container) => new PhpRenderer(
                $container->get('path.templates')
            ),
        ]);
    }
}
