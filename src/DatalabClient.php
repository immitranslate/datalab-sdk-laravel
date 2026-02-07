<?php

namespace ImmiTranslate\Datalab;

use ImmiTranslate\Datalab\Requests\MarkerRequest;
use ImmiTranslate\Datalab\Requests\ExtractionSchemaRequest;

class DatalabClient
{
    public function __construct(
        protected string $endpoint,
        protected string $apiKey,
        protected int $markerPollIntervalSeconds = 5,
        protected int $extractionSchemaPollIntervalSeconds = 5,
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

    public function generateSchemas(?int $pollIntervalSeconds = null): ExtractionSchemaRequest
    {
        return new ExtractionSchemaRequest(
            endpoint: $this->endpoint,
            apiKey: $this->apiKey,
            defaultPollIntervalSeconds: $this->extractionSchemaPollIntervalSeconds,
            pollIntervalSeconds: $pollIntervalSeconds,
        );
    }
}
