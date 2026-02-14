<?php

namespace JordJD\FuzzyEvents\TestClasses;

use JordJD\FuzzyEvents\Interfaces\FuzzyListenerInterface;

class Greeting implements FuzzyListenerInterface
{
    public function handle(string $query)
    {
        return 'Hello there!';
    }
}
