<?php

namespace ImmiTranslate\Datalab\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ImmiTranslate\Datalab\Datalab
 */
class Datalab extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \ImmiTranslate\Datalab\Datalab::class;
    }
}
