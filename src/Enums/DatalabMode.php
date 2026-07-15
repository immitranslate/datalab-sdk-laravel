<?php

namespace ImmiTranslate\Datalab\Enums;

enum DatalabMode: string
{
    /** Lowest latency, good for simple documents. Best for high-throughput pipelines and simple layouts. */
    case Fast = 'fast';

    /** Balance of speed and accuracy. Recommended for most use cases. */
    case Balanced = 'balanced';

    /** Highest accuracy. Best for complex tables, dense layouts, and scanned documents. */
    case Accurate = 'accurate';
}
