<?php

namespace ImmiTranslate\Datalab\Enums;

enum DatalabMode: string
{
    case Fast = 'fast';
    case Balanced = 'balanced';
    case Accurate = 'accurate';
}
