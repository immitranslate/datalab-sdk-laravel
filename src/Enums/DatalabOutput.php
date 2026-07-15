<?php

namespace ImmiTranslate\Datalab\Enums;

enum DatalabOutput: string
{
    /** Includes bounding boxes and block types. Best for programmatic access to blocks. */
    case Json = 'json';

    /** Preserves visual structure. Best for web display. */
    case Html = 'html';

    /** Most compatible (default). Best for LLM/RAG pipelines. */
    case Markdown = 'markdown';

    /** Pre-chunked for vector databases. Best for embedding and search. */
    case Chunks = 'chunks';
}
