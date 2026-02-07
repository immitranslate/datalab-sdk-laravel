<?php

namespace ImmiTranslate\Datalab\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ImmiTranslate\Datalab\DatalabClient
 *
 * @method static \ImmiTranslate\Datalab\Requests\MarkerRequest marker(?int $pollIntervalSeconds = null)
 * @method static \ImmiTranslate\Datalab\Requests\ExtractionSchemaRequest generateSchemas(?int $pollIntervalSeconds = null)
 */
class Datalab extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'datalab';
    }
}
