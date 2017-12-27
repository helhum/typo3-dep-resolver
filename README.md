# TYPO3 Dependency Resolving API

## Installation
`composer require typo3/dep-resolver`

## Usage

```php
$listenersToBeOrdered = [
    FooListener::class => [],
    BarListener::class => [
        'before' => [FooListener::class],
        'after' => [BazListener::class],
    ],
    BazListener::class => [],
];
$orderingService = new \TYPO3\DependencyOrdering\DependencyOrderingService();
$orderedListeners = $orderingService->orderByDependencies($listenersToBeOrdered);
var_export($orderedListeners);

/* Outputs:
array (
  'BazListener' => 
  array (
  ),
  'BarListener' => 
  array (
    'before' => 
    array (
      0 => 'FooListener',
    ),
    'after' => 
    array (
      0 => 'BazListener',
    ),
  ),
  'FooListener' => 
  array (
  ),
)
*/
```
