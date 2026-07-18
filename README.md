# Fuzzy Events

[![Tests](https://github.com/Jord-JD/fuzzy-events/actions/workflows/tests.yml/badge.svg)](https://github.com/Jord-JD/fuzzy-events/actions/workflows/tests.yml)

Fuzzy events is a PHP package that allows you to perform actions based on a 
fuzzy string matches.

## Installation

Install using the following Composer command.

```bash
composer require jord-jd/fuzzy-events
```

### Usage

See the following usage example.

```php
class Greeting implements FuzzyListenerInterface
{

    public function handle(string $query)
    {
        return 'Hello there!';
    }
}
```

```php
$listeners = [
    Greeting::class => [
        'Hello',
        'Hi',
        'Hey',
        'Greetings',
        'Howdy',
        'Hello there',
        'Hi there',
    ],
];

$confidenceThreshold = 75;

$dispatcher = new FuzzyDispatcher($listeners, $confidenceThreshold);

$response = $dispatcher->fire('Greetingz!');

// $response = 'Hello there!'

try {
    $dispatcher->fire('Goodbye!');
} catch (ConfidenceTooLowException $e) {
    // No matches within specified confidence threshold!
}

$confidences = $dispatcher->getConfidences('Hi!');

// $confidences = [
//    Greeting::class => 80
// ]
```

## Ranked matches

Use `getRankedConfidences()` when you want every candidate sorted from best to worst, or `getMatches()` to keep only candidates above the dispatcher's threshold (or an explicit minimum).

```php
$ranked = $dispatcher->getRankedConfidences('Hello');
$matches = $dispatcher->getMatches('Hello');
$strictMatches = $dispatcher->getMatches('Hello', 95);
```

Confidence and match thresholds must be between 0 and 100. Listener classes and phrases are validated when the dispatcher is constructed, so configuration errors fail before a query is handled.

Matching remains case-sensitive by default. Pass `false` as the optional third constructor argument to compare case-insensitively.

```php
$dispatcher = new FuzzyDispatcher($listeners, 75, false);
```
