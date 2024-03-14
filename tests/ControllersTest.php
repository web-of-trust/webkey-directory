<?php

namespace Wkd\Tests;

use Wkd\Controller\BaseController;
use PsrDiscovery\Discover;
use Psr\Http\Message\{
    ResponseInterface,
    ServerRequestInterface,
};


class ControllersTest extends TestCase
{
    public function testBaseController()
    {
        $app = $this->getAppInstance();
        $testController = new class($this->runner->getContainer()) extends BaseController {
            protected function action(
                ServerRequestInterface $request,
                ResponseInterface $response,
                array $args
            ): ResponseInterface
            {
                $response->getBody()->write('test controller');
                return $response->withStatus(200);
            }
        };

        $app->get('/test-controller', $testController);
        $request = $this->createRequest('GET', '/test-controller');
        $response = $app->handle($request);

        $payload = (string) $response->getBody();
        $this->assertEquals('test controller', $payload);
        $this->assertEquals(200, $response->getStatusCode());
    }

    private function createRequest(
        string $method, string $path, array $serverParams = []
    )
    {
        return Discover::httpRequestFactory()->createServerRequest($method, $path, $serverParams);
    } 
}
