<?php

namespace JordJD\FuzzyEvents\Interfaces;

interface FuzzyListenerInterface
{
    public function handle(string $query);
}
