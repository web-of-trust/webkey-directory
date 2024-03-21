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
use Psr\Container\ContainerInterface;

/**
 * Abstract runner class
 *
 * @package  Wkd
 * @category Application
 * @author   Nguyen Van Nguyen - nguyennv1981@gmail.com
 */
abstract class AbstractRunner implements RunnerInterface
{
    const APP_NAME    = 'Webkey Directory';
    const APP_PATH    = '/';
    const APP_VERSION = '1.0.0';

    /**
     * Psr container
     *
     * @var ContainerInterface
     */
    protected readonly ContainerInterface $container;

    /**
     * Constructor
     *
     * @param string $baseDir
     * @return self
     */
    public function __construct(string $baseDir) {
        $builder = new ContainerBuilder();
        $builder->addDefinitions([
            ...self::appConfig(),
            ...self::pathConfig($baseDir),
        ]);
        $this->serviceDefinitions($builder);
        $this->container = $builder->build();
    }

    /**
     * {@inheritdoc}
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Service definitions.
     *
     * @param ContainerBuilder $builder.
     * @see https://php-di.org/doc/php-definitions.html
     */
    abstract protected function serviceDefinitions(ContainerBuilder $builder): void;

    private static function appConfig(): array
    {
        return [
            'app.name'              => \DI\env('APP_NAME', self::APP_NAME),
            'app.version'           => \DI\env('APP_VERSION', self::APP_VERSION),
            'app.path'              => \DI\env('APP_PATH', self::APP_PATH),
            'error.display.details' => \DI\env('ERROR_DISPLAY_DETAILS', false),
            'error.log'             => \DI\env('ERROR_LOG', true),
            'error.log.details'     => \DI\env('ERROR_LOG_DETAILS', true),
            'key.extension'         => \DI\env('KEY_EXTENSION', '.asc'),
        ];
    }

    private static function pathConfig(string $baseDir): array
    {
        return [
            'path.base' => $baseDir,
            'path.storage' => \DI\string('{path.base}/storage'),
            'path.templates' => \DI\string('{path.base}/templates'),
            'wkd.storage' => \DI\string('{path.storage}/wkd'),
            'vks.storage' => \DI\string('{path.storage}/vks'),
            'vks.storage.fingerprint' => \DI\string('{vks.storage}/fingerprint'),
            'vks.storage.keyid' => \DI\string('{vks.storage}/keyid'),
            'vks.storage.email' => \DI\string('{vks.storage}/email'),
        ];
    }
}
