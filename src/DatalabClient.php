<?php

namespace ImmiTranslate\Datalab;

use ImmiTranslate\Datalab\Requests\ExtractionSchemaRequest;
use ImmiTranslate\Datalab\Requests\FormFillingRequest;
use ImmiTranslate\Datalab\Requests\MarkerRequest;

class DatalabClient
{
    public function __construct(
        protected string $endpoint,
        protected string $apiKey,
        protected int $markerPollIntervalSeconds = 5,
        protected int $extractionSchemaPollIntervalSeconds = 5,
        protected int $formFillingPollIntervalSeconds = 5,
    ) {}

    /**
     * @deprecated Datalab is deprecating the Marker API. It is being replaced by the Convert API.
     */
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

    public function formFilling(?int $pollIntervalSeconds = null): FormFillingRequest
    {
        return new FormFillingRequest(
            endpoint: $this->endpoint,
            apiKey: $this->apiKey,
            defaultPollIntervalSeconds: $this->formFillingPollIntervalSeconds,
            pollIntervalSeconds: $pollIntervalSeconds,
        );
    }
}
