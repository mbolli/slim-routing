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

namespace Jgut\Slim\Routing\Mapping\Driver;

use Jgut\Mapping\Exception\DriverException;
use Jgut\Slim\Routing\Mapping\Annotation\Group as GroupAnnotation;
use Jgut\Slim\Routing\Mapping\Annotation\Route as RouteAnnotation;
use Jgut\Slim\Routing\Mapping\Annotation\Router as RouterAnnotation;
use Jgut\Slim\Routing\Mapping\Metadata\GroupMetadata;
use Jgut\Slim\Routing\Mapping\Metadata\RouteMetadata;

trait ClassDriverTrait
{
    /**
     * @param \ReflectionClass|\ReflectionMethod $what
     * @param string                             $attribute
     *
     * @return GroupAnnotation|RouterAnnotation|RouteAnnotation|null
     */
    abstract protected function getAnnotation(
        \ReflectionClass|\ReflectionMethod $what,
        string $attribute
    ): GroupAnnotation|RouterAnnotation|RouteAnnotation|null;

    /**
     * {@inheritdoc}
     *
     * @return RouteMetadata[]
     */
    public function getMetadata(): array
    {
        $routes = [];

        $mappingClasses = $this->getMappingClasses();

        $groups = $this->getGroupsMetadata($mappingClasses);

        foreach ($mappingClasses as $class) {
            if ($class->isAbstract()) {
                continue;
            }

            $router = $this->getAnnotation($class, RouterAnnotation::class);
            if ($router !== null) {
                $routes[] = $this->getRoutesMetadata($class, $groups);
            }
        }

        return \count($routes) > 0 ? array_merge(...$routes) : [];
    }

    /**
     * Get groups metadata.
     *
     * @param \ReflectionClass[] $mappingClasses
     *
     * @throws DriverException
     *
     * @return GroupMetadata[]
     */
    protected function getGroupsMetadata(array $mappingClasses): array
    {
        $groups = [];

        foreach ($mappingClasses as $class) {
            /** @var GroupAnnotation|null $group */
            $group = $this->getAnnotation($class, GroupAnnotation::class);

            if ($group !== null) {
                $groups[$class->getName()] = [
                    'parent' => $group->getParent(),
                    'group' => $this->getGroupMetadata($group),
                ];
            }
        }

        /** @var GroupMetadata[] $groups */
        $groups = array_map(
            function (array $groupDataBag) use ($groups): GroupMetadata {
                /** @var GroupMetadata $group */
                $group = $groupDataBag['group'];
                $parent = $groupDataBag['parent'];

                if ($parent !== null) {
                    if (!isset($groups[$parent])) {
                        throw new DriverException(sprintf('Parent group %s does not exist', $parent));
                    }

                    $group->setParent($groups[$parent]['group']);
                }

                return $group;
            },
            $groups
        );

        return $groups;
    }

    /**
     * Get group metadata.
     *
     * @param GroupAnnotation $annotation
     *
     * @return GroupMetadata
     */
    protected function getGroupMetadata(GroupAnnotation $annotation): GroupMetadata
    {
        $group = new GroupMetadata();
        $group->setPlaceholders($annotation->getPlaceholders());
        $group->setMiddleware($annotation->getMiddleware());

        if ($annotation->getPattern() !== null) {
            $group->setPattern($annotation->getPattern());
        }

        if ($annotation->getPrefix() !== null) {
            $group->setPrefix($annotation->getPrefix());
        }

        $group->setParameters($annotation->getParameters());
        $group->setArguments($annotation->getArguments());

        return $group;
    }

    /**
     * Get processed routes.
     *
     * @param \ReflectionClass $class
     * @param GroupMetadata[]  $groups
     *
     * @throws DriverException
     *
     * @return RouteMetadata[]
     */
    protected function getRoutesMetadata(\ReflectionClass $class, array $groups): array
    {
        $routes = [];

        $group = null;

        /** @var GroupAnnotation|null $groupAnnotation */
        $groupAnnotation = $this->getAnnotation($class, GroupAnnotation::class);

        if ($groupAnnotation !== null) {
            $group = $groups[$class->getName()];
        }

        foreach ($class->getMethods() as $method) {
            /** @var RouteAnnotation|null $route */
            $route = $this->getAnnotation($method, RouteAnnotation::class);

            if ($route !== null) {
                if ($method->isConstructor() || $method->isDestructor()) {
                    throw new DriverException(
                        sprintf('Routes can not be defined in constructor or destructor in class %s', $class->name)
                    );
                }

                $modifiers = array_intersect(
                    ['private', 'protected'],
                    \Reflection::getModifierNames($method->getModifiers())
                );
                if (\count($modifiers) !== 0) {
                    throw new DriverException(
                        sprintf('Routes can not be defined in private or protected methods in class %s', $class->name)
                    );
                }

                $routes[] = $this->getRouteMetadata($class, $method, $route, $group);
            }
        }

        if (\count($routes) === 0) {
            throw new DriverException(sprintf('Class %s does not define any route', $class->name));
        }

        return $routes;
    }

    /**
     * Get processed route.
     *
     * @param \ReflectionClass   $class
     * @param \ReflectionMethod  $method
     * @param RouteAnnotation    $annotation
     * @param GroupMetadata|null $group
     *
     * @throws DriverException
     *
     * @return RouteMetadata
     */
    protected function getRouteMetadata(
        \ReflectionClass $class,
        \ReflectionMethod $method,
        RouteAnnotation $annotation,
        ?GroupMetadata $group = null
    ): RouteMetadata {
        $route = (new RouteMetadata());
        $route->setPlaceholders($annotation->getPlaceholders());
        $route->setMiddleware($annotation->getMiddleware());
        $route->setMethods($annotation->getMethods());
        $route->setXmlHttpRequest($annotation->isXmlHttpRequest());
        $route->setInvokable($class->name . ':' . $method->name);
        $route->setPriority($annotation->getPriority());

        if ($annotation->getPattern() !== null) {
            $route->setPattern($annotation->getPattern());
        }

        if ($annotation->getName() !== null) {
            $route->setName($annotation->getName());
        }

        if ($group !== null) {
            $route->setGroup($group);
        }

        if ($annotation->getTransformer() !== null) {
            $route->setTransformer($annotation->getTransformer())
                ->setParameters($this->getRouteParameters($method, $annotation));
        }

        $route->setArguments($annotation->getArguments());

        return $route;
    }

    /**
     * Get route parameters.
     *
     * @param \ReflectionMethod $method
     * @param RouteAnnotation   $annotation
     *
     * @return mixed[]
     */
    protected function getRouteParameters(
        \ReflectionMethod $method,
        RouteAnnotation $annotation
    ): array {
        $parameters = [];
        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type !== null) {
                $parameters[$parameter->getName()] = $type->getName();
            }
        }

        return array_merge($parameters, $annotation->getParameters());
    }
}
