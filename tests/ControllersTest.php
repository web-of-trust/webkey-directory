<?php

namespace Wkd\Tests;

use DI\Bridge\Slim\Bridge;
use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Factory\AppFactory;
use Wkd\Application\{
    RouteDefinitions,
    SlimRunner,
};
use Wkd\Controller\{
    BaseController,
    HomeController,
    SearchController,
    HkpController,
    VksController,
    WkdController,
};
use PsrDiscovery\Discover;
use Psr\Http\Message\{
    ResponseInterface,
    ServerRequestInterface,
};

class ControllersTest extends TestCase
{
    private $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = (new SlimRunner(dirname(__DIR__)))->getContainer();
        AppFactory::setResponseFactory(Discover::httpResponseFactory());
        AppFactory::setStreamFactory(Discover::httpStreamFactory());
    }

    private function getAppInstance(): App
    {
        $app = Bridge::create($this->container);
        (new RouteDefinitions())($app);
        return $app;
    }

    public function testBaseController()
    {
        $app = $this->getAppInstance();
        $controller = new class($this->container) extends BaseController {
            protected function action(
                ServerRequestInterface $request,
                ResponseInterface $response,
                array $args
            ): ResponseInterface
            {
                $response->getBody()->write('test content');
                return $response->withStatus(200);
            }
        };

        $app->get('/test', $controller);
        $request = $this->createRequest('GET', '/test');

        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());

        $payload = (string) $response->getBody();
        $this->assertEquals('test content', $payload);
    }

    public function testHomeController()
    {
        $app = $this->getAppInstance();
        $controller = $this->container->get(HomeController::class);

        $app->get('/home', $controller);
        $request = $this->createRequest('GET', '/home');

        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());

        $payload = (string) $response->getBody();
        $this->assertStringContainsString(
            $this->container->get('app.name'),
            $payload
        );
    }

    public function testSearchController()
    {
        $app = $this->getAppInstance();
        $controller = $this->container->get(SearchController::class);

        $app->get('/test-search', $controller);
        $request = $this->createRequest('GET', '/test-search');

        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());

        $payload = (string) $response->getBody();
        $this->assertStringContainsString(
            $this->container->get('app.name'),
            $payload
        );
        $this->assertStringContainsString(
            'No key found for',
            $payload
        );
    }

    public function testHkpController()
    {
        $app = $this->getAppInstance();
        $controller = $this->container->get(HkpController::class);

        $app->get('/pks', $controller);
        $request = $this->createRequest('GET', '/pks');
        $response = $app->handle($request);
        $this->assertEquals(404, $response->getStatusCode());

        $payload = (string) $response->getBody();
        $this->assertStringContainsString(
            'No key found for',
            $payload
        );

        $request = $this->createRequest('GET', '/pks')->withQueryParams([
            'search' => 'user-01@example.com',
        ]);
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());

        $payload = (string) $response->getBody();
        $this->assertStringContainsString(
            'BEGIN PGP PUBLIC KEY BLOCK',
            $payload
        );
    }

    public function testVksController()
    {
        $app = $this->getAppInstance();
        $controller = $this->container->get(VksController::class);

        $app->get('/vks/by-fingerprint/{fingerprint}', $controller);
        $request = $this->createRequest('GET', '/vks/by-fingerprint/3d8b4357fd879a68b17cd63e515fd6d483835295');
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $payload = (string) $response->getBody();
        $this->assertStringContainsString(
            'BEGIN PGP PUBLIC KEY BLOCK',
            $payload
        );

        $app = $this->getAppInstance();
        $app->get('/vks/by-keyid/{keyid}', $controller);
        $request = $this->createRequest('GET', '/vks/by-keyid/0c78729346288572');
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $payload = (string) $response->getBody();
        $this->assertStringContainsString(
            'BEGIN PGP PUBLIC KEY BLOCK',
            $payload
        );

        $app = $this->getAppInstance();
        $app->get('/vks/by-email/{email}', $controller);
        $request = $this->createRequest('GET', '/vks/by-email/user-01%40example.com');
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $payload = (string) $response->getBody();
        $this->assertStringContainsString(
            'BEGIN PGP PUBLIC KEY BLOCK',
            $payload
        );
    }

    public function testWkdController()
    {
        $app = $this->getAppInstance();
        $controller = $this->container->get(WkdController::class);

        $app->get('/wkd/{domain}/hu/{hash}', $controller);
        $request = $this->createRequest('GET', '/wkd/example.com/hu/xcmq6doy4p6yx4oxlms2giblpmekojtu');
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $payload = (string) $response->getBody();
        $this->assertStringContainsString(
            'BEGIN PGP PUBLIC KEY BLOCK',
            $payload
        );
    }

    private function createRequest(
        string $method, string $path, array $serverParams = []
    )
    {
        return Discover::httpRequestFactory()->createServerRequest($method, $path, $serverParams);
    } 
}
