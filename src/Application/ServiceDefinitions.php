<?php declare(strict_types=1);
/**
 * This file is part of the Webkey Directory project.
 *
 * Licensed under GNU Affero General Public License v3.0
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wkd\Application;

use DI\ContainerBuilder;
use PsrDiscovery\Discover;
use Psr\Container\ContainerInterface as Container;
use Psr\Log\{
    LoggerInterface,
    NullLogger,
};
use Slim\Views\PhpRenderer;
use Symfony\Component\Console\Application as ConsoleApplication;
use Wkd\Command\SyncKeyCommand;

/**
 * Service definitions class
 * 
 * @package  Wkd
 * @category Application
 * @author   Nguyen Van Nguyen - nguyennv1981@gmail.com
 */
final class ServiceDefinitions
{
    /**
     * Add service definitions.
     *
     * @param ContainerBuilder $builder.
     * @see https://php-di.org/doc/php-definitions.html
     */
    public function __invoke(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([
            LoggerInterface::class => fn () => Discover::log() ?? new NullLogger(),
            PhpRenderer::class => fn (Container $container) => new PhpRenderer(
                $container->get('path.templates')
            ),
            ConsoleApplication::class => function (Container $container) {
                $console = new ConsoleApplication(
                    $container->get('app.name'),
                    $container->get('app.version'),
                );
                $console->addCommands([
                    new SyncKeyCommand(),
                ]);

                return $console;
            },
        ]);
    }
}
