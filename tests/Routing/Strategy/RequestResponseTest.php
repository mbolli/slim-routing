<?php

/*
 * slim-routing (https://github.com/juliangut/slim-routing).
 * Slim framework routing.
 *
 * @license BSD-3-Clause
 * @link https://github.com/juliangut/slim-routing
 * @author Julián Gutiérrez <juliangut@gmail.com>
 */

declare(strict_types=1);

namespace Jgut\Slim\Routing\Tests\Strategy;

use Jgut\Slim\Routing\Strategy\RequestResponse;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ResponseFactory;
use Zend\Diactoros\ServerRequestFactory;

/**
 * Default route callback strategy with route parameters as an array of arguments tests.
 */
class RequestResponseTest extends TestCase
{
    public function testDispatch(): void
    {
        $responseFactory = new ResponseFactory();
        $container = $this->getMockBuilder(ContainerInterface::class)
            ->getMock();
        /* @var ContainerInterface $container */
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $response = $this->getMockBuilder(ResponseInterface::class)
            ->getMock();
        /* @var ResponseInterface $response */

        $strategy = new RequestResponse([], $responseFactory, $container);

        $callback = function (ServerRequestInterface $request) use ($responseFactory) {
            $this->assertEquals('value', $request->getAttribute('param'));

            $response = $responseFactory->createResponse();
            $response->getBody()->write('Return content');

            return $response;
        };

        $return = $strategy($callback, $request, $response, ['param' => 'value']);

        $this->assertEquals('Return content', (string) $return->getBody());
    }
}
