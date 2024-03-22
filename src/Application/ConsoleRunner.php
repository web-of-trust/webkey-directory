<?php declare(strict_types=1);
/**
 * This file is part of the Webkey Privacy project.
 *
 * Licensed under GNU Affero General Public License v3.0
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wkd\Application;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface as Container;
use Symfony\Component\Console\Application;
use Wkd\Command\SyncKeyCommand;

/**
 * Console runner class
 * Run the symfony console application.
 *
 * @package  Wkd
 * @category Application
 * @author   Nguyen Van Nguyen - nguyennv1981@gmail.com
 */
final class ConsoleRunner extends AbstractRunner
{
    /**
     * {@inheritdoc}
     */
    public function run(): void
    {
        exit(
            $this->container->get(Application::class)->run()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function serviceDefinitions(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([
            SyncKeyCommand::class => fn (Container $c) => new SyncKeyCommand($c),
            Application::class => function (Container $c) {
                $console = new Application(
                    $c->get('app.name'),
                    $c->get('app.version'),
                );
                $console->addCommands([
                    $c->get(SyncKeyCommand::class),
                ]);

                return $console;
            },
        ]);
    }
}
