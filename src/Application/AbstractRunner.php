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
    private readonly ContainerInterface $container;

    /**
     * Constructor
     *
     * @param string $baseDir
     * @return self
     */
    public function __construct(private readonly string $baseDir) {
        $builder = new ContainerBuilder();
        $builder->addDefinitions([
            ...self::appConfig(),
            ...self::pathConfig($baseDir),
        ]);

        self::loadServices($builder);
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
     * Load services
     * 
     * @param ContainerBuilder $builder
     * @return void
     */
    private static function loadServices(ContainerBuilder $builder): void
    {
        (new ServiceDefinitions())($builder);
    }

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
        $storagePath = implode([
            $baseDir,
            DIRECTORY_SEPARATOR,
            'storage',
        ]);
        $vksStorage = implode([
            $storagePath,
            DIRECTORY_SEPARATOR,
            'vks',
        ]);
        return [
            'path.storage' => $storagePath,
            'path.templates' => implode([
                $baseDir,
                DIRECTORY_SEPARATOR,
                'templates',
            ]),
            'wkd.storage' => implode([
                $storagePath,
                DIRECTORY_SEPARATOR,
                'wkd',
            ]),
            'vks.fingerprint.storage' => implode([
                $vksStorage,
                DIRECTORY_SEPARATOR,
                'fingerprint',
            ]),
            'vks.keyid.storage' => implode([
                $vksStorage,
                DIRECTORY_SEPARATOR,
                'keyid',
            ]),
            'vks.email.storage' => implode([
                $vksStorage,
                DIRECTORY_SEPARATOR,
                'email',
            ]),
        ];
    }
}
