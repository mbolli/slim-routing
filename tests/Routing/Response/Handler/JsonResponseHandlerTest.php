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

namespace Jgut\Slim\Routing\Tests\Response\Handler;

use Jgut\Slim\Routing\Response\Handler\JsonResponseHandler;
use Jgut\Slim\Routing\Response\PayloadResponse;
use Jgut\Slim\Routing\Tests\Stubs\ResponseStub;
use Laminas\Diactoros\ResponseFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Generic JSON response handler tests.
 */
class JsonResponseHandlerTest extends TestCase
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();
    }

    public function testInvalidResponseType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Response type should be an instance of Jgut\Slim\Routing\Response\PayloadResponse'
        );

        $responseFactory = $this->getMockBuilder(ResponseFactoryInterface::class)
            ->getMock();

        (new JsonResponseHandler($responseFactory))->handle(new ResponseStub($this->request));
    }

    public function testNonEncodableResponseType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Response type payload is not json encodable');

        $responseFactory = $this->getMockBuilder(ResponseFactoryInterface::class)
            ->getMock();

        (new JsonResponseHandler($responseFactory))
            ->handle(new PayloadResponse(['data' => stream_context_create()], $this->request));
    }

    public function testHandleCollapsed(): void
    {
        $responseFactory = new ResponseFactory();

        $response = (new JsonResponseHandler($responseFactory))
            ->handle(new PayloadResponse(['data' => ['param' => 'value']], $this->request));

        static::assertInstanceOf(ResponseInterface::class, $response);
        static::assertEquals('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));
        static::assertEquals('{"data":{"param":"value"}}', (string) $response->getBody());
    }

    public function testHandlePrettified(): void
    {
        $responseFactory = new ResponseFactory();

        $response = (new JsonResponseHandler($responseFactory, true))
            ->handle(new PayloadResponse(['data' => ['param' => 'value']], $this->request));

        static::assertInstanceOf(ResponseInterface::class, $response);
        static::assertEquals('application/json; charset=utf-8', $response->getHeaderLine('Content-Type'));

        $responseContent = <<<'JSON'
{
    "data": {
        "param": "value"
    }
}
JSON;
        static::assertEquals($responseContent, (string) $response->getBody());
    }
}
