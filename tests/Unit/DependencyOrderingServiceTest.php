<?php
declare(strict_types=1);
namespace TYPO3\DependencyOrdering\Tests\Unit;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use PHPUnit\Framework\TestCase;
use TYPO3\DependencyOrdering\DependencyOrderingService;

/**
 * Test case
 */
class DependencyOrderingServiceTest extends TestCase
{
    /**
     * @test
     * @dataProvider orderByDependenciesBuildsCorrectOrderDataProvider
     * @param array $items
     * @param string $beforeKey
     * @param string $afterKey
     * @param array $expectedOrderedItems
     */
    public function orderByDependenciesBuildsCorrectOrder(array $items, $beforeKey, $afterKey, array $expectedOrderedItems)
    {
        $orderedItems = (new DependencyOrderingService())->orderByDependencies($items, $beforeKey, $afterKey);
        $this->assertSame($expectedOrderedItems, $orderedItems);
    }

    /**
     * @return array
     */
    public function orderByDependenciesBuildsCorrectOrderDataProvider(): array
    {
        return [
            'unordered' => [
                [ // $items
                    1 => [],
                    2 => [],
                ],
                'before',
                'after',
                [ // $expectedOrderedItems
                    1 => [],
                    2 => [],
                ],
            ],
            'ordered' => [
                [ // $items
                    1 => [],
                    2 => [
                        'precedes' => [ 1 ],
                    ],
                ],
                'precedes',
                'after',
                [ // $expectedOrderedItems
                    2 => [
                        'precedes' => [ 1 ],
                    ],
                    1 => [],
                ],
            ],
            'mixed' => [
                [ // $items
                    1 => [],
                    2 => [
                        'before' => [ 1 ],
                    ],
                    3 => [
                        'otherProperty' => true,
                    ],
                ],
                'before',
                'after',
                [ // $expectedOrderedItems
                    2 => [
                        'before' => [ 1 ],
                    ],
                    1 => [],
                    3 => [
                        'otherProperty' => true,
                    ],
                ],
            ],
            'reference to non-existing' => [
                [ // $items
                    2 => [
                        'before' => [ 1 ],
                        'depends' => [ 3 ],
                    ],
                    3 => [
                        'otherProperty' => true,
                    ],
                ],
                'before',
                'depends',
                [ // $expectedOrderedItems
                    3 => [
                        'otherProperty' => true,
                    ],
                    2 => [
                        'before' => [ 1 ],
                        'depends' => [ 3 ],
                    ],
                ],
            ],
            'multiple dependencies' => [
                [ // $items
                    1 => [
                        'depends' => [ 3, 2, 4 ],
                    ],
                    2 => [],
                    3 => [
                        'depends' => [ 2 ],
                    ],
                ],
                'before',
                'depends',
                [ // $expectedOrderedItems
                    2 => [],
                    3 => [
                        'depends' => [ 2 ],
                    ],
                    1 => [
                        'depends' => [ 3, 2, 4 ],
                    ],
                ],
            ],
            'direct dependency is moved up' => [
                [ // $items
                    1 => [],
                    2 => [],
                    3 => [
                        'depends' => [ 1 ],
                    ],
                ],
                'before',
                'depends',
                [ // $expectedOrderedItems
                    1 => [],
                    3 => [
                        'depends' => [ 1 ],
                    ],
                    2 => [],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider buildDependencyGraphBuildsValidGraphDataProvider
     * @param array $dependencies
     * @param array $expectedGraph
     */
    public function buildDependencyGraphBuildsValidGraph(array $dependencies, array $expectedGraph)
    {
        $graph = (new DependencyOrderingService())->buildDependencyGraph($dependencies);
        $this->assertEquals($expectedGraph, $graph);
    }

    /**
     * @return array
     */
    public function buildDependencyGraphBuildsValidGraphDataProvider(): array
    {
        return [
            'graph1' => [
                [ // dependencies
                    1 => [
                        'before' => [],
                        'after' => [ 2 ],
                    ],
                ],
                [ // graph
                    1 => [
                        1 => false,
                        2 => true,
                    ],
                    2 => [
                        1 => false,
                        2 => false,
                    ],
                ],
            ],
            'graph2' => [
                [ // dependencies
                    1 => [
                        'before' => [ 3 ],
                        'after' => [ 2 ],
                    ],
                ],
                [ // graph
                    1 => [
                        1 => false,
                        2 => true,
                        3 => false,
                    ],
                    2 => [
                        1 => false,
                        2 => false,
                        3 => false,
                    ],
                    3 => [
                        1 => true,
                        2 => false,
                        3 => false,
                    ],
                ],
            ],
            'graph3' => [
                [ // dependencies
                    3 => [
                        'before' => [],
                        'after' => [],
                    ],
                    1 => [
                        'before' => [ 3 ],
                        'after' => [ 2 ],
                    ],
                    2 => [
                        'before' => [ 3 ],
                        'after' => [],
                    ],
                ],
                [ // graph
                    1 => [
                        1 => false,
                        2 => true,
                        3 => false,
                    ],
                    2 => [
                        1 => false,
                        2 => false,
                        3 => false,
                    ],
                    3 => [
                        1 => true,
                        2 => true,
                        3 => false,
                    ],
                ],
            ],
            'cyclic graph' => [
                [ // dependencies
                    1 => [
                        'before' => [ 2 ],
                        'after' => [],
                    ],
                    2 => [
                        'before' => [ 1 ],
                        'after' => [],
                    ],
                ],
                [ // graph
                    1 => [
                        1 => false,
                        2 => true,
                    ],
                    2 => [
                        1 => true,
                        2 => false,
                    ],
                ],
            ],
            'TYPO3 Flow Packages' => [
                [ // dependencies
                    'TYPO3.Flow' => [
                        'before' => [],
                        'after' => ['Symfony.Component.Yaml', 'Doctrine.Common', 'Doctrine.DBAL', 'Doctrine.ORM'],
                    ],
                    'Doctrine.ORM' => [
                        'before' => [],
                        'after' => ['Doctrine.Common', 'Doctrine.DBAL'],
                    ],
                    'Doctrine.Common' => [
                        'before' => [],
                        'after' => [],
                    ],
                    'Doctrine.DBAL' => [
                        'before' => [],
                        'after' => ['Doctrine.Common'],
                    ],
                    'Symfony.Component.Yaml' => [
                        'before' => [],
                        'after' => [],
                    ],
                ],
                [ // graph
                    'TYPO3.Flow' => [
                        'TYPO3.Flow' => false,
                        'Doctrine.ORM' => true,
                        'Doctrine.Common' => true,
                        'Doctrine.DBAL' => true,
                        'Symfony.Component.Yaml' => true,
                    ],
                    'Doctrine.ORM' => [
                        'TYPO3.Flow' => false,
                        'Doctrine.ORM' => false,
                        'Doctrine.Common' => true,
                        'Doctrine.DBAL' => true,
                        'Symfony.Component.Yaml' => false,
                    ],
                    'Doctrine.Common' => [
                        'TYPO3.Flow' => false,
                        'Doctrine.ORM' => false,
                        'Doctrine.Common' => false,
                        'Doctrine.DBAL' => false,
                        'Symfony.Component.Yaml' => false,
                    ],
                    'Doctrine.DBAL' => [
                        'TYPO3.Flow' => false,
                        'Doctrine.ORM' => false,
                        'Doctrine.Common' => true,
                        'Doctrine.DBAL' => false,
                        'Symfony.Component.Yaml' => false,
                    ],
                    'Symfony.Component.Yaml' => [
                        'TYPO3.Flow' => false,
                        'Doctrine.ORM' => false,
                        'Doctrine.Common' => false,
                        'Doctrine.DBAL' => false,
                        'Symfony.Component.Yaml' => false,
                    ],
                ],
            ],
            'TYPO3 CMS Extensions' => [
                [ // dependencies
                    'core' => [
                        'before' => [],
                        'after' => [],
                    ],
                    'openid' => [
                        'before' => [],
                        'after' => ['core', 'setup'],
                    ],
                    'scheduler' => [
                        'before' => [],
                        'after' => ['core'],
                    ],
                    'setup' => [
                        'before' => [],
                        'after' => ['core'],
                    ],
                ],
                [ // graph
                    'core' => [
                        'core' => false,
                        'setup' => false,
                        'scheduler' => false,
                        'openid' => false,
                    ],
                    'openid' => [
                        'core' => true,
                        'setup' => true,
                        'scheduler' => false,
                        'openid' => false,
                    ],
                    'scheduler' => [
                        'core' => true,
                        'setup' => false,
                        'scheduler' => false,
                        'openid' => false,
                    ],
                    'setup' => [
                        'core' => true,
                        'setup' => false,
                        'scheduler' => false,
                        'openid' => false,
                    ],
                ],
            ],
            'Dummy Packages' => [
                [ // dependencies
                    'A' => [
                        'before' => [],
                        'after' => ['B', 'D', 'C'],
                    ],
                    'B' => [
                        'before' => [],
                        'after' => [],
                    ],
                    'C' => [
                        'before' => [],
                        'after' => ['E'],
                    ],
                    'D' => [
                        'before' => [],
                        'after' => ['E'],
                    ],
                    'E' => [
                        'before' => [],
                        'after' => [],
                    ],
                    'F' => [
                        'before' => [],
                        'after' => [],
                    ],
                ],
                [ // graph
                    'A' => [
                        'A' => false,
                        'B' => true,
                        'C' => true,
                        'D' => true,
                        'E' => false,
                        'F' => false,
                    ],
                    'B' => [
                        'A' => false,
                        'B' => false,
                        'C' => false,
                        'D' => false,
                        'E' => false,
                        'F' => false,
                    ],
                    'C' => [
                        'A' => false,
                        'B' => false,
                        'C' => false,
                        'D' => false,
                        'E' => true,
                        'F' => false,
                    ],
                    'D' => [
                        'A' => false,
                        'B' => false,
                        'C' => false,
                        'D' => false,
                        'E' => true,
                        'F' => false,
                    ],
                    'E' => [
                        'A' => false,
                        'B' => false,
                        'C' => false,
                        'D' => false,
                        'E' => false,
                        'F' => false,
                    ],
                    'F' => [
                        'A' => false,
                        'B' => false,
                        'C' => false,
                        'D' => false,
                        'E' => false,
                        'F' => false,
                    ],
                ],
            ],
            'Suggestions without reverse dependency' => [
                [ // dependencies
                    'A' => [
                        'before' => [],
                        'after' => [],
                        'after-resilient' => ['B'], // package suggestion
                    ],
                    'B' => [
                        'before' => [],
                        'after' => [],
                    ],
                    'C' => [
                        'before' => [],
                        'after' => ['A'],
                    ],
                ],
                [ // graph
                    'A' => [
                        'A' => false,
                        'B' => true,
                        'C' => false,
                    ],
                    'B' => [
                        'A' => false,
                        'B' => false,
                        'C' => false,
                    ],
                    'C' => [
                        'A' => true,
                        'B' => false,
                        'C' => false,
                    ],
                ],
            ],
            'Suggestions with reverse dependency' => [
                [ // dependencies
                    'A' => [
                        'before' => [],
                        'after' => [],
                        'after-resilient' => ['B'], // package suggestion
                    ],
                    'B' => [
                        'before' => [],
                        'after' => ['A'],
                    ],
                    'C' => [
                        'before' => [],
                        'after' => ['A'],
                    ],
                ],
                [ // graph
                    'A' => [
                        'A' => false,
                        'B' => false,
                        'C' => false,
                    ],
                    'B' => [
                        'A' => true,
                        'B' => false,
                        'C' => false,
                    ],
                    'C' => [
                        'A' => true,
                        'B' => false,
                        'C' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider calculateOrderResolvesCorrectOrderDataProvider
     * @param array $graph
     * @param array $expectedList
     */
    public function calculateOrderResolvesCorrectOrder(array $graph, array $expectedList)
    {
        $list = (new DependencyOrderingService())->calculateOrder($graph);
        $this->assertSame($expectedList, $list);
    }

    /**
     * @return array
     */
    public function calculateOrderResolvesCorrectOrderDataProvider(): array
    {
        return [
            'list1' => [
                [ // $graph
                    1 => [
                        1 => false,
                        2 => true,
                    ],
                    2 => [
                        1 => false,
                        2 => false,
                    ],
                ],
                [ // $expectedList
                    2, 1,
                ],
            ],
            'list2' => [
                [ // $graph
                    1 => [
                        1 => false,
                        2 => true,
                        3 => false,
                    ],
                    2 => [
                        1 => false,
                        2 => false,
                        3 => false,
                    ],
                    3 => [
                        1 => true,
                        2 => true,
                        3 => false,
                    ],
                ],
                [ // $expectedList
                    2, 1, 3,
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function calculateOrderDetectsCyclicGraph()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1381960494);

        (new DependencyOrderingService())->calculateOrder([
            1 => [
                1 => false,
                2 => true,
            ],
            2 => [
                1 => true,
                2 => false,
            ],
        ]);
    }
}
