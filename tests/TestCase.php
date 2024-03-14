<?php declare(strict_types=1);

namespace Wkd\Tests;

use DI\Bridge\Slim\Bridge;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Slim\App;
use Wkd\Application\RouteDefinitions;
use Wkd\Application\SlimRunner;

class TestCase extends PHPUnitTestCase
{
    use ProphecyTrait;

    protected $runner;
    protected $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->runner = new SlimRunner(dirname(__DIR__));
        $this->faker  = \Faker\Factory::create();
    }

    /**
     * @return App
     */
    protected function getAppInstance(): App
    {
        $app = Bridge::create($this->runner->getContainer());
        $app->addRoutingMiddleware();
        (new RouteDefinitions())($app);
        return $app;
    }
}
