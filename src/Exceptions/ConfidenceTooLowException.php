<?php

namespace JordJD\FuzzyEvents\Exceptions;

use Exception;

class ConfidenceTooLowException extends Exception
{
    public function __construct(float $confidenceThreshold, float $actualConfidence)
    {
        return parent::__construct(
            'Confidence is too low. '.
            'Confidence threshold is set to '.number_format($confidenceThreshold, 2).'%, '.
            'but the best match had a confidence of '.number_format($actualConfidence, 2).'%.'
        );
    }
}
