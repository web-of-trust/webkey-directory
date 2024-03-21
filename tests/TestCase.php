<?php declare(strict_types=1);

namespace Wkd\Tests;

use DI\Bridge\Slim\Bridge;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Slim\App;
use Wkd\Application\RouteDefinitions;
use Wkd\Application\SlimRunner;

class TestCase extends PHPUnitTestCase
{
    protected $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = (new SlimRunner(dirname(__DIR__)))->getContainer();
    }

    /**
     * @return App
     */
    protected function getAppInstance(): App
    {
        $app = Bridge::create($this->container);
        $app->addRoutingMiddleware();
        (new RouteDefinitions())($app);
        return $app;
    }
}
