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

namespace Jgut\Slim\Routing\Tests\Mapping\Annotation;

use Jgut\Slim\Routing\Mapping\Annotation\Group;
use PHPUnit\Framework\TestCase;

/**
 * Group annotation tests.
 */
class GroupTest extends TestCase
{
    /**
     * @var Group
     */
    protected $annotation;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->annotation = new Group();
    }

    public function testDefaults(): void
    {
        static::assertEquals('', $this->annotation->getParent());
    }

    public function testParent(): void
    {
        $this->annotation->setParent('groupName');

        static::assertEquals('groupName', $this->annotation->getParent());
    }

    public function testWrongPrefix(): void
    {
        $this->expectException(\Jgut\Mapping\Exception\AnnotationException::class);
        $this->expectExceptionMessage('Group prefixes must not contain spaces');

        new Group(prefix: 'a prefix');
    }

    public function testPrefix(): void
    {
        $this->annotation->setPrefix('prefix');

        static::assertEquals('prefix', $this->annotation->getPrefix());
    }
}
