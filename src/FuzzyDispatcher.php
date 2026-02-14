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

    public function __construct(array $listeners, float $confidenceThreshold)
    {
        if (!$listeners) {
            throw new InvalidArgumentException('No listeners defined.');
        }

        $this->listeners = $listeners;
        $this->confidenceThreshold = $confidenceThreshold;
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
        $confidences = $this->getConfidences($query);

        arsort($confidences);

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

        foreach($this->listeners as $listenerClassName => $phrases) {
            $confidence = 0;

            foreach($phrases as $phrase) {
                similar_text($query, $phrase, $phraseConfidence);
                if ($phraseConfidence > $confidence) {
                    $confidence = $phraseConfidence;
                }
            }

            $confidences[$listenerClassName] = $confidence;
        }

        return $confidences;
    }
}
