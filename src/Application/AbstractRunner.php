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
use Wkd\Enum\Environment;

/**
 * Abstract runner class
 *
 * @package  Wkd
 * @category Application
 * @author   Nguyen Van Nguyen - nguyennv1981@gmail.com
 */
abstract class AbstractRunner implements RunnerInterface
{
    const APP_NAME    = 'webkey-directory';
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
     * Determine if the application is in the production environment.
     *
     * @return bool
     */
    public static function isProduction()
    {
        $environment = Environment::tryFrom(
            self::env('APP_ENV') ?? ''
        ) ?? Environment::Development;
        return $environment === Environment::Production;
    }

    /**
     * Retrieve an environment-specific configuration setting
     *
     * @param string $key
     * @return string|null
     */
    public static function env(string $key): string|null
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return $value ?: null;
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
            'app.name'      => \DI\env('APP_NAME', self::APP_NAME),
            'app.env'       => \DI\env('APP_ENV', Environment::Development->value),
            'app.version'   => \DI\env('APP_VERSION', self::APP_VERSION),
            'error.display' => \DI\env('ERROR_DISPLAY', true),
            'error.log'     => \DI\env('ERROR_LOG', true),
            'error.details' => \DI\env('ERROR_DETAILS', true),
            'logger.name'   => \DI\env('LOGGER_NAME', self::APP_NAME),
            'logger.level'  => \DI\env('LOGGER_LEVEL', 'info'),
            'logger.file'   => \DI\env('LOGGER_FILE', '/var/log/' . self::APP_NAME . '.log'),
        ];
    }

    private static function pathConfig(string $baseDir): array
    {
        return [
            'path.storage' => implode([
                $baseDir,
                DIRECTORY_SEPARATOR,
                'storage',
            ]),
            'path.templates' => implode([
                $baseDir,
                DIRECTORY_SEPARATOR,
                'templates',
            ]),
            'wkd.storage' => implode([
                $baseDir,
                DIRECTORY_SEPARATOR,
                'storage',
                DIRECTORY_SEPARATOR,
                'wkd',
            ]),
            'vks.fingerprint.storage' => implode([
                $baseDir,
                DIRECTORY_SEPARATOR,
                'storage',
                DIRECTORY_SEPARATOR,
                'vks',
                DIRECTORY_SEPARATOR,
                'fingerprint',
            ]),
            'vks.keyid.storage' => implode([
                $baseDir,
                DIRECTORY_SEPARATOR,
                'storage',
                DIRECTORY_SEPARATOR,
                'vks',
                DIRECTORY_SEPARATOR,
                'keyid',
            ]),
            'vks.email.storage' => implode([
                $baseDir,
                DIRECTORY_SEPARATOR,
                'storage',
                DIRECTORY_SEPARATOR,
                'vks',
                DIRECTORY_SEPARATOR,
                'email',
            ]),
        ];
    }
}
