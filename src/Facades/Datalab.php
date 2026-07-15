<?php

namespace ImmiTranslate\Datalab\Facades;

use Illuminate\Support\Facades\Facade;
use ImmiTranslate\Datalab\DatalabClient;

/**
 * @see DatalabClient
 *
 * @method static \ImmiTranslate\Datalab\Requests\ConvertRequest convert(?int $pollIntervalSeconds = null)
 * @method static \ImmiTranslate\Datalab\Requests\MarkerRequest marker(?int $pollIntervalSeconds = null) Deprecated: Datalab is deprecating the Marker API. Use convert() instead.
 * @method static \ImmiTranslate\Datalab\Requests\ExtractionSchemaRequest generateSchemas(?int $pollIntervalSeconds = null)
 * @method static \ImmiTranslate\Datalab\Requests\FormFillingRequest formFilling(?int $pollIntervalSeconds = null)
 */
class Datalab extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'datalab';
    }
}
