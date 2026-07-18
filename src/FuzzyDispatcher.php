<?php

namespace JordJD\FuzzyEvents;

use JordJD\FuzzyEvents\Exceptions\ConfidenceTooLowException;
use JordJD\FuzzyEvents\Interfaces\FuzzyListenerInterface;
use InvalidArgumentException;

class FuzzyDispatcher
{
    /**
     * @var array
     */
    private $listeners;

    /**
     * @var float
     */
    private $confidenceThreshold;

    /**
     * @var bool
     */
    private $caseSensitive;

    public function __construct(array $listeners, float $confidenceThreshold, bool $caseSensitive = true)
    {
        if (!$listeners) {
            throw new InvalidArgumentException('No listeners defined.');
        }

        if (!is_finite($confidenceThreshold) || $confidenceThreshold < 0 || $confidenceThreshold > 100) {
            throw new InvalidArgumentException('The confidence threshold must be between 0 and 100.');
        }

        foreach ($listeners as $listenerClassName => $phrases) {
            if (!is_string($listenerClassName) || !is_a($listenerClassName, FuzzyListenerInterface::class, true)) {
                throw new InvalidArgumentException('Every listener must implement FuzzyListenerInterface.');
            }

            if (!is_array($phrases) || !$phrases) {
                throw new InvalidArgumentException('Every listener must define at least one phrase.');
            }

            foreach ($phrases as $phrase) {
                if (!is_string($phrase) || $phrase === '') {
                    throw new InvalidArgumentException('Listener phrases must be non-empty strings.');
                }
            }
        }

        $this->listeners = $listeners;
        $this->confidenceThreshold = $confidenceThreshold;
        $this->caseSensitive = $caseSensitive;
    }

    public function fire(string $query)
    {
        $listener = $this->getListener($query);

        return $listener->handle($query);
    }

    public function getListener(string $query): ?FuzzyListenerInterface
    {
        $className = $this->getListenerClassName($query);

        return new $className;
    }

    public function getListenerClassName(string $query): ?string
    {
        $confidences = $this->getRankedConfidences($query);

        $listenerClassNames = array_keys($confidences);

        $listenerClassName = $listenerClassNames[0];
        $highestConfidence = $confidences[$listenerClassName];

        if ($highestConfidence < $this->confidenceThreshold) {
            throw new ConfidenceTooLowException($this->confidenceThreshold, $highestConfidence);
        }

        return $listenerClassNames[0];
    }

    public function getConfidences(string $query): array
    {
        $confidences = [];
        $queryToCompare = $this->caseSensitive ? $query : strtolower($query);

        foreach ($this->listeners as $listenerClassName => $phrases) {
            $confidence = 0;

            foreach ($phrases as $phrase) {
                $phraseToCompare = $this->caseSensitive ? $phrase : strtolower($phrase);
                similar_text($queryToCompare, $phraseToCompare, $phraseConfidence);
                if ($phraseConfidence > $confidence) {
                    $confidence = $phraseConfidence;
                }
            }

            $confidences[$listenerClassName] = $confidence;
        }

        return $confidences;
    }

    /**
     * Return all listener confidences from best to worst.
     */
    public function getRankedConfidences(string $query): array
    {
        $confidences = $this->getConfidences($query);
        arsort($confidences);

        return $confidences;
    }

    /**
     * Return ranked listeners that meet a minimum confidence.
     */
    public function getMatches(string $query, ?float $minimumConfidence = null): array
    {
        if ($minimumConfidence === null) {
            $minimumConfidence = $this->confidenceThreshold;
        }

        if (!is_finite($minimumConfidence) || $minimumConfidence < 0 || $minimumConfidence > 100) {
            throw new InvalidArgumentException('The minimum confidence must be between 0 and 100.');
        }

        return array_filter(
            $this->getRankedConfidences($query),
            function ($confidence) use ($minimumConfidence) {
                return $confidence >= $minimumConfidence;
            }
        );
    }
}
