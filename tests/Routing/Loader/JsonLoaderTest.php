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

namespace Jgut\Slim\Routing\Tests\Loader;

use Jgut\Slim\Routing\Loader\JsonLoader;
use PHPUnit\Framework\TestCase;

/**
 * JSON loader tests.
 */
class JsonLoaderTest extends TestCase
{
    /**
     * @var JsonLoader
     */
    protected $loader;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->loader = new JsonLoader();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Path "\non\existing\path" does not exist
     */
    public function testInvalidPath()
    {
        $this->loader->load(['\non\existing\path']);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp /^Routing file .+ should return an array$/
     */
    public function testInvalidPathReturn()
    {
        $this->loader->load([__DIR__ . '/../Files/files/invalid']);
    }

    public function testValidPath()
    {
        $routing = $this->loader->load([__DIR__ . '/../Files/files/valid']);

        $loaded = [
            'parameter1' => 'B',
            'parameter2' => ['A', 'B'],
        ];

        self::assertEquals($loaded, $routing);
    }
}
