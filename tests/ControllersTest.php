<?php

namespace Wkd\Tests;

use Wkd\Controller\{
    BaseController,
    HomeController,
    SearchController,
    HkpController,
};
use PsrDiscovery\Discover;
use Psr\Http\Message\{
    ResponseInterface,
    ServerRequestInterface,
};


class ControllersTest extends TestCase
{
    private $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = $this->getAppInstance();
    }

    public function testBaseController()
    {
        $controller = new class($this->runner->getContainer()) extends BaseController {
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

        $this->app->get('/test', $controller);
        $request = $this->createRequest('GET', '/test');

        $response = $this->app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());

        $payload = (string) $response->getBody();
        $this->assertEquals('test content', $payload);
    }

    public function testHomeController()
    {
        $controller = $this->runner->getContainer()->get(HomeController::class);
        $this->app->get('/home', $controller);
        $request = $this->createRequest('GET', '/home');

        $response = $this->app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());

        $payload = (string) $response->getBody();
        $this->assertStringContainsString(
            $this->runner->getContainer()->get('app.name'),
            $payload
        );
    }

    public function testSearchController()
    {
        $controller = $this->runner->getContainer()->get(SearchController::class);
        $this->app->get('/test-search', $controller);
        $request = $this->createRequest('GET', '/test-search');

        $response = $this->app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());

        $payload = (string) $response->getBody();
        $this->assertStringContainsString(
            $this->runner->getContainer()->get('app.name'),
            $payload
        );
        $this->assertStringContainsString(
            'No key found for',
            $payload
        );
    }

    public function testHkpController()
    {
        $controller = $this->runner->getContainer()->get(HkpController::class);
        $this->app->get('/pks', $controller);

        $request = $this->createRequest('GET', '/pks');
        $response = $this->app->handle($request);
        $this->assertEquals(404, $response->getStatusCode());

        $payload = (string) $response->getBody();
        $this->assertStringContainsString(
            'No key found for',
            $payload
        );

        $request = $this->createRequest('GET', '/pks')->withQueryParams([
            'search' => 'info@webkey.net.vn',
        ]);
        $response = $this->app->handle($request);
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
