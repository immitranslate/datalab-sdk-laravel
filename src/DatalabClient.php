<?php

namespace ImmiTranslate\Datalab;

use ImmiTranslate\Datalab\Requests\MarkerRequest;

class DatalabClient
{
    public function __construct(
        protected string $endpoint,
        protected string $apiKey,
        protected int $markerPollIntervalSeconds = 5,
    ) {}

    public function marker(?int $pollIntervalSeconds = null): MarkerRequest
    {
        return new MarkerRequest(
            endpoint: $this->endpoint,
            apiKey: $this->apiKey,
            defaultPollIntervalSeconds: $this->markerPollIntervalSeconds,
            pollIntervalSeconds: $pollIntervalSeconds,
        );
    }
}
