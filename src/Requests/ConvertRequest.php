<?php

namespace ImmiTranslate\Datalab\Requests;

use Illuminate\Support\Facades\Http;
use ImmiTranslate\Datalab\DTO\ConvertResponse;
use ImmiTranslate\Datalab\DTO\ConvertResultResponse;
use ImmiTranslate\Datalab\Enums\DatalabExtra;
use ImmiTranslate\Datalab\Enums\DatalabMode;
use ImmiTranslate\Datalab\Enums\DatalabOutput;
use InvalidArgumentException;
use RuntimeException;

/**
 * Convert PDFs, Word documents, spreadsheets, and images to machine-readable formats
 * (markdown, HTML, JSON, or chunks). Handles complex layouts, tables, math, and images.
 *
 * File limits: maximum file size is 200 MB, with up to 7,000 pages per request.
 * Results are deleted from Datalab servers one hour after processing completes —
 * retrieve your results promptly.
 */
class ConvertRequest
{
    /** @var array<string, mixed> */
    protected array $payload = [];

    /** @var array<int, array{name: string, contents: string, filename: string}> */
    protected array $files = [];

    protected int $pollIntervalSeconds;

    public function __construct(
        protected string $endpoint,
        protected string $apiKey,
        protected int $defaultPollIntervalSeconds = 5,
        ?int $pollIntervalSeconds = null,
    ) {
        $this->payload['mode'] = DatalabMode::Fast->value;
        $this->pollIntervalSeconds = max(0, $pollIntervalSeconds ?? $this->defaultPollIntervalSeconds);
    }

    /**
     * Optional file URL (http/https). If provided, the server will download and process it.
     */
    public function fileUrl(?string $fileUrl): static
    {
        return $this->set('file_url', $fileUrl);
    }

    /**
     * Which output mode to use. Valid values: 'fast' (lowest latency), 'balanced'
     * (balanced accuracy and latency), 'accurate' (highest accuracy).
     *
     * 'balanced' is recommended for most use cases; use 'fast' for simple, clean PDFs
     * at high throughput and 'accurate' for scanned documents, complex tables, or
     * dense layouts. Defaults to 'fast'.
     */
    public function mode(DatalabMode $mode): static
    {
        return $this->set('mode', $mode->value);
    }

    /**
     * The maximum number of pages in the document to convert.
     */
    public function maxPages(?int $maxPages): static
    {
        return $this->set('max_pages', $maxPages);
    }

    /**
     * The page range to convert, comma separated like 0,5-10,20 (0-indexed). Overrides
     * max_pages if provided. For spreadsheets, filters by sheet index instead.
     */
    public function pageRange(?string $pageRange): static
    {
        return $this->set('page_range', $pageRange);
    }

    /**
     * Whether to paginate the output. Each page will be separated by a horizontal
     * rule with the page number.
     */
    public function paginate(bool $paginate = true): static
    {
        return $this->setBoolean('paginate', $paginate);
    }

    /**
     * Add data-block-id attributes to HTML elements for citation tracking.
     * Only applies when the output format includes 'html'.
     */
    public function addBlockIds(bool $addBlockIds = true): static
    {
        return $this->setBoolean('add_block_ids', $addBlockIds);
    }

    /**
     * Include markdown field in chunks and JSON output.
     */
    public function includeMarkdownInChunks(bool $includeMarkdownInChunks = true): static
    {
        return $this->setBoolean('include_markdown_in_chunks', $includeMarkdownInChunks);
    }

    /**
     * Disable image extraction from the document.
     */
    public function disableImageExtraction(bool $disableImageExtraction = true): static
    {
        return $this->setBoolean('disable_image_extraction', $disableImageExtraction);
    }

    /**
     * Disable synthetic image captions/descriptions in output.
     */
    public function disableImageCaptions(bool $disableImageCaptions = true): static
    {
        return $this->setBoolean('disable_image_captions', $disableImageCaptions);
    }

    /**
     * When enabled, predict per-word bounding boxes with confidence scores for each page.
     * Each word is inlined into the HTML output as a span carrying data-bbox and
     * data-confidence attributes (markdown output strips these).
     *
     * Requires the 'html' output format to expose the attributes. Billed at $0.30 per
     * 1K pages on top of the base conversion rate.
     */
    public function wordBboxes(bool $wordBboxes = true): static
    {
        return $this->setBoolean('word_bboxes', $wordBboxes);
    }

    /**
     * Wrap synthetic image captions with HTML comment markers for easy identification/removal.
     */
    public function fenceSyntheticCaptions(bool $fenceSyntheticCaptions = true): static
    {
        return $this->setBoolean('fence_synthetic_captions', $fenceSyntheticCaptions);
    }

    /**
     * The output format. Can be 'json', 'html', 'markdown', or 'chunks'.
     * Defaults to 'markdown'. Pass an array for multiple formats.
     *
     * @param  DatalabOutput|array<int, DatalabOutput>  $outputFormat
     */
    public function outputFormat(DatalabOutput|array $outputFormat): static
    {
        if ($outputFormat instanceof DatalabOutput) {
            return $this->set('output_format', $outputFormat->value);
        }

        if ($outputFormat === []) {
            unset($this->payload['output_format']);

            return $this;
        }

        foreach ($outputFormat as $format) {
            if (! $format instanceof DatalabOutput) {
                throw new InvalidArgumentException(
                    'When passing an array to outputFormat(), every item must be a '.DatalabOutput::class.' enum.'
                );
            }
        }

        $formats = array_map(
            static fn (DatalabOutput $format): string => $format->value,
            $outputFormat
        );

        return $this->set('output_format', implode(',', $formats));
    }

    /**
     * Variadic convenience for outputFormat(), e.g. outputFormats(DatalabOutput::Markdown, DatalabOutput::Json).
     */
    public function outputFormats(DatalabOutput ...$outputFormats): static
    {
        return $this->outputFormat($outputFormats);
    }

    /**
     * Optimize markdown for LLM token usage (compact tables, single-space indents).
     */
    public function tokenEfficientMarkdown(bool $tokenEfficientMarkdown = true): static
    {
        return $this->setBoolean('token_efficient_markdown', $tokenEfficientMarkdown);
    }

    /**
     * Skip the cache and re-run the conversion.
     */
    public function skipCache(bool $skipCache = true): static
    {
        return $this->setBoolean('skip_cache', $skipCache);
    }

    /**
     * Save a checkpoint after conversion. The checkpoint_id in the response can be
     * used with /extract or /segment to skip re-parsing.
     */
    public function saveCheckpoint(bool $saveCheckpoint = true): static
    {
        return $this->setBoolean('save_checkpoint', $saveCheckpoint);
    }

    /**
     * Additional configuration as a JSON string (an array will be JSON-encoded). Supported keys:
     * 'keep_pageheader_in_output', 'keep_pagefooter_in_output', 'keep_spreadsheet_formatting'.
     */
    public function additionalConfig(array|string|null $additionalConfig): static
    {
        return $this->setJsonOrString('additional_config', $additionalConfig);
    }

    /**
     * Optional workflow step data ID to associate with this request.
     */
    public function workflowStepDataId(?int $workflowStepDataId): static
    {
        return $this->set('workflowstepdata_id', $workflowStepDataId);
    }

    /**
     * Comma-separated list of extra features: 'track_changes', 'chart_understanding',
     * 'table_cell_bboxes' (per-cell bounding boxes for each table, instead of one box for
     * the whole table; includes word bounding boxes), 'list_item_bboxes' (per-item bounding
     * boxes for each list or list group, instead of one box for the whole list; includes
     * word bounding boxes), 'extract_links', 'infographic', 'new_block_types'.
     *
     * The bounding box add-ons ('table_cell_bboxes', 'list_item_bboxes') require the 'html'
     * output format to expose the attributes and are each billed at $0.30 per 1K pages on
     * top of the base conversion rate. ('table_row_bboxes' is deprecated — use
     * 'table_cell_bboxes' instead.)
     *
     * @param  DatalabExtra|array<int, DatalabExtra|string>|string|null  $extras
     */
    public function extras(DatalabExtra|array|string|null $extras): static
    {
        if ($extras === null) {
            unset($this->payload['extras']);

            return $this;
        }

        if ($extras instanceof DatalabExtra) {
            return $this->set('extras', $extras->value);
        }

        if (is_array($extras)) {
            $values = array_map(
                static fn (DatalabExtra|string $extra): string => $extra instanceof DatalabExtra ? $extra->value : $extra,
                $extras
            );

            return $this->set('extras', implode(',', $values));
        }

        return $this->set('extras', $extras);
    }

    /**
     * Variadic convenience for extras(), e.g. extrasList(DatalabExtra::ExtractLinks, DatalabExtra::Infographic).
     */
    public function extrasList(DatalabExtra ...$extras): static
    {
        return $this->extras($extras);
    }

    /**
     * Optional webhook URL to call when the request is complete. Overrides the webhook URL
     * configured at the account level for this request only.
     */
    public function webhookUrl(?string $webhookUrl): static
    {
        return $this->set('webhook_url', $webhookUrl);
    }

    /**
     * Optional residency region override (e.g. 'us', 'eu'). When provided, use fileUrl();
     * multipart uploads are rejected. When omitted, the request uses the team's configured
     * residency and profile. EU processing carries a regional pricing premium.
     */
    public function processingLocation(?string $processingLocation): static
    {
        return $this->set('processing_location', $processingLocation);
    }

    /**
     * Optional eval rubric ID to run evaluation after conversion.
     */
    public function evalRubricId(?int $evalRubricId): static
    {
        return $this->set('eval_rubric_id', $evalRubricId);
    }

    /**
     * Input PDF, word document, powerpoint, or image file, uploaded as multipart form data.
     * Images must be png, jpg, or webp format.
     */
    public function file(string $path, ?string $filename = null): static
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException("File path [{$path}] is not readable.");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new InvalidArgumentException("Failed to read file [{$path}].");
        }

        $this->files[] = [
            'name' => 'file',
            'contents' => $contents,
            'filename' => $filename ?? basename($path),
        ];

        return $this;
    }

    /**
     * Alias of executeSync().
     */
    public function execute(): ConvertResultResponse
    {
        return $this->executeSync();
    }

    /**
     * Submit the conversion and poll /convert/{request_id} until a terminal status is reached.
     */
    public function executeSync(): ConvertResultResponse
    {
        $submission = $this->executeAsync();

        if (! $submission->isSuccess() || $submission->requestId === null) {
            throw new RuntimeException(
                'Unable to start convert request. Check request payload and API key configuration.'
            );
        }

        do {
            $result = $this->checkResult($submission->requestId);

            if ($result->isTerminalStatus()) {
                return $result;
            }

            if ($this->pollIntervalSeconds > 0) {
                usleep($this->pollIntervalSeconds * 1_000_000);
            }
        } while (true);
    }

    /**
     * Submit the conversion and return the initial response (request_id and
     * request_check_url) without polling for the result.
     */
    public function executeAsync(): ConvertResponse
    {
        if ($this->apiKey === '') {
            throw new InvalidArgumentException(
                'Datalab API key is not configured. Set datalab-sdk-laravel.api_key.'
            );
        }

        $request = Http::asMultipart()
            ->acceptJson()
            ->withHeaders([
                'X-API-Key' => $this->apiKey,
            ]);

        foreach ($this->files as $file) {
            $request = $request->attach(
                $file['name'],
                $file['contents'],
                $file['filename']
            );
        }

        return ConvertResponse::fromHttpResponse(
            $request->post($this->resolveUrl('convert'), $this->payload)
        );
    }

    /**
     * Fetch the current result for a previously submitted request. Results are deleted
     * from Datalab servers one hour after processing completes.
     */
    public function checkResult(string $requestId): ConvertResultResponse
    {
        return ConvertResultResponse::fromHttpResponse(
            Http::acceptJson()
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                ])
                ->get($this->resolveUrl('convert/'.rawurlencode($requestId)))
        );
    }

    protected function resolveUrl(string $path): string
    {
        return rtrim($this->endpoint, '/').'/'.ltrim($path, '/');
    }

    protected function set(string $key, mixed $value): static
    {
        if ($value === null) {
            unset($this->payload[$key]);

            return $this;
        }

        $this->payload[$key] = $value;

        return $this;
    }

    protected function setBoolean(string $key, bool $value): static
    {
        return $this->set($key, $value ? 'true' : 'false');
    }

    protected function setJsonOrString(string $key, array|string|null $value): static
    {
        if ($value === null) {
            unset($this->payload[$key]);

            return $this;
        }

        if (is_array($value)) {
            return $this->set($key, json_encode($value, JSON_THROW_ON_ERROR));
        }

        return $this->set($key, $value);
    }
}
