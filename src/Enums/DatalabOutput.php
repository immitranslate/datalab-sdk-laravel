<?php

namespace ImmiTranslate\Datalab\Enums;

enum DatalabOutput: string
{
    case Json = 'json';
    case Html = 'html';
    case Markdown = 'markdown';
    case Chunks = 'chunks';
}
