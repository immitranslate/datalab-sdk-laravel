<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use ImmiTranslate\Datalab\DTO\ConvertResponse;
use ImmiTranslate\Datalab\DTO\ConvertResultResponse;
use ImmiTranslate\Datalab\DTO\ValidationDetail;
use ImmiTranslate\Datalab\Enums\DatalabExtra;
use ImmiTranslate\Datalab\Enums\DatalabMode;
use ImmiTranslate\Datalab\Enums\DatalabOutput;
use ImmiTranslate\Datalab\Enums\DatalabParseQuality;
use ImmiTranslate\Datalab\Facades\Datalab;

it('sends convert request with fluent api', function () {
    Http::fake([
        '*' => Http::response([
            'request_id' => 'req_123',
            'request_check_url' => 'https://www.datalab.to/api/v1/convert/req_123',
            'success' => true,
            'error' => null,
            'versions' => [],
        ], 200),
    ]);

    $response = Datalab::convert()
        ->fileUrl('https://r2.aws.com/test.pdf')
        ->mode(DatalabMode::Fast)
        ->outputFormat(DatalabOutput::Json)
        ->webhookUrl('https://testwebhook.com/test123')
        ->executeAsync();

    expect($response->isSuccess())->toBeTrue();

    Http::assertSent(function (Request $request): bool {
        $body = $request->body();

        return $request->method() === 'POST'
            && $request->url() === 'https://www.datalab.to/api/v1/convert'
            && $request->hasHeader('X-API-Key', 'test-api-key')
            && str_contains($body, 'name="file_url"')
            && str_contains($body, 'https://r2.aws.com/test.pdf')
            && str_contains($body, 'name="mode"')
            && str_contains($body, 'fast')
            && str_contains($body, 'name="output_format"')
            && str_contains($body, 'json')
            && str_contains($body, 'name="webhook_url"')
            && str_contains($body, 'https://testwebhook.com/test123');
    });
});

it('supports multiple output formats and enum extras', function () {
    Http::fake([
        '*' => Http::response([
            'request_id' => 'req_456',
            'request_check_url' => 'https://www.datalab.to/api/v1/convert/req_456',
            'success' => true,
            'error' => null,
            'versions' => [],
        ], 200),
    ]);

    Datalab::convert()
        ->outputFormats(DatalabOutput::Markdown, DatalabOutput::Html)
        ->extras([DatalabExtra::ExtractLinks, DatalabExtra::NewBlockTypes])
        ->paginate()
        ->executeAsync();

    Http::assertSent(function (Request $request): bool {
        $body = $request->body();

        return str_contains($body, 'name="output_format"')
            && str_contains($body, 'markdown,html')
            && str_contains($body, 'name="extras"')
            && str_contains($body, 'extract_links,new_block_types')
            && str_contains($body, 'name="paginate"')
            && str_contains($body, 'true');
    });
});

it('supports extras as variadic enums, mixed arrays, and strings', function () {
    Http::fake([
        '*' => Http::response([
            'request_id' => 'req_extras',
            'request_check_url' => 'https://www.datalab.to/api/v1/convert/req_extras',
            'success' => true,
            'error' => null,
            'versions' => [],
        ], 200),
    ]);

    Datalab::convert()
        ->extrasList(DatalabExtra::ChartUnderstanding, DatalabExtra::Infographic)
        ->executeAsync();

    Http::assertSent(function (Request $request): bool {
        return str_contains($request->body(), 'chart_understanding,infographic');
    });

    Datalab::convert()
        ->extras([DatalabExtra::TrackChanges, 'future_flag'])
        ->executeAsync();

    Http::assertSent(function (Request $request): bool {
        return str_contains($request->body(), 'track_changes,future_flag');
    });

    Datalab::convert()
        ->extras(DatalabExtra::TableCellBboxes)
        ->executeAsync();

    Http::assertSent(function (Request $request): bool {
        return str_contains($request->body(), 'table_cell_bboxes');
    });
});

it('sends convert specific options', function () {
    Http::fake([
        '*' => Http::response([
            'request_id' => 'req_opts',
            'request_check_url' => 'https://www.datalab.to/api/v1/convert/req_opts',
            'success' => true,
            'error' => null,
            'versions' => [],
        ], 200),
    ]);

    Datalab::convert()
        ->fileUrl('https://r2.aws.com/test.pdf')
        ->includeMarkdownInChunks()
        ->wordBboxes()
        ->fenceSyntheticCaptions()
        ->tokenEfficientMarkdown()
        ->processingLocation('eu')
        ->evalRubricId(42)
        ->saveCheckpoint()
        ->executeAsync();

    Http::assertSent(function (Request $request): bool {
        $body = $request->body();

        return str_contains($body, 'name="include_markdown_in_chunks"')
            && str_contains($body, 'name="word_bboxes"')
            && str_contains($body, 'name="fence_synthetic_captions"')
            && str_contains($body, 'name="token_efficient_markdown"')
            && str_contains($body, 'name="processing_location"')
            && str_contains($body, 'eu')
            && str_contains($body, 'name="eval_rubric_id"')
            && str_contains($body, '42')
            && str_contains($body, 'name="save_checkpoint"');
    });
});

it('supports attaching a local file for conversion', function () {
    Http::fake([
        '*' => Http::response([
            'request_id' => 'req_file',
            'request_check_url' => 'https://www.datalab.to/api/v1/convert/req_file',
            'success' => true,
            'error' => null,
            'versions' => [],
        ], 200),
    ]);

    $path = tempnam(sys_get_temp_dir(), 'datalab');
    file_put_contents($path, '%PDF-1.4 test');

    Datalab::convert()
        ->file($path, 'document.pdf')
        ->executeAsync();

    unlink($path);

    Http::assertSent(function (Request $request): bool {
        $body = $request->body();

        return str_contains($body, 'name="file"')
            && str_contains($body, 'filename="document.pdf"')
            && str_contains($body, '%PDF-1.4 test');
    });
});

it('maps a 200 convert response into dto', function () {
    Http::fake([
        '*' => Http::response([
            'request_id' => 'req_123',
            'request_check_url' => 'https://www.datalab.to/api/v1/convert/req_123',
            'success' => true,
            'error' => null,
            'versions' => [
                'convert' => '2026.07.15',
            ],
        ], 200),
    ]);

    $response = Datalab::convert()->executeAsync();

    expect($response)->toBeInstanceOf(ConvertResponse::class)
        ->and($response->status)->toBe(200)
        ->and($response->requestId)->toBe('req_123')
        ->and($response->requestCheckUrl)->toBe('https://www.datalab.to/api/v1/convert/req_123')
        ->and($response->success)->toBeTrue()
        ->and($response->error)->toBeNull()
        ->and($response->versions)->toBe(['convert' => '2026.07.15'])
        ->and($response->detail)->toBe([])
        ->and($response->isSuccess())->toBeTrue()
        ->and($response->isValidationError())->toBeFalse();
});

it('maps a 422 convert response into dto', function () {
    Http::fake([
        '*' => Http::response([
            'detail' => [
                [
                    'loc' => ['body', 'file_url'],
                    'msg' => 'Invalid file URL',
                    'type' => 'value_error.url',
                ],
            ],
        ], 422),
    ]);

    $response = Datalab::convert()->executeAsync();

    expect($response)->toBeInstanceOf(ConvertResponse::class)
        ->and($response->status)->toBe(422)
        ->and($response->requestId)->toBeNull()
        ->and($response->success)->toBeNull()
        ->and($response->detail)->toHaveCount(1)
        ->and($response->detail[0])->toBeInstanceOf(ValidationDetail::class)
        ->and($response->detail[0]->loc)->toBe(['body', 'file_url'])
        ->and($response->detail[0]->msg)->toBe('Invalid file URL')
        ->and($response->detail[0]->type)->toBe('value_error.url')
        ->and($response->isSuccess())->toBeFalse()
        ->and($response->isValidationError())->toBeTrue();
});

it('execute sync polls convert result endpoint until complete', function () {
    Http::fake([
        'https://www.datalab.to/api/v1/convert' => Http::response([
            'request_id' => 'req_sync_1',
            'request_check_url' => 'https://www.datalab.to/api/v1/convert/req_sync_1',
            'success' => true,
            'error' => null,
            'versions' => [],
        ], 200),
        'https://www.datalab.to/api/v1/convert/req_sync_1' => Http::sequence()
            ->push([
                'status' => 'processing',
                'success' => null,
            ], 200)
            ->push([
                'status' => 'complete',
                'output_format' => 'markdown',
                'markdown' => '# Parsed',
                'success' => true,
                'error' => null,
                'page_count' => 3,
                'runtime' => 2.5,
                'parse_quality_score' => 4.2,
                'checkpoint_id' => 'chk_1',
                'versions' => ['convert' => '2026.07.15'],
            ], 200),
    ]);

    $response = Datalab::convert(0)->executeSync();

    expect($response)->toBeInstanceOf(ConvertResultResponse::class)
        ->and($response->status)->toBe('complete')
        ->and($response->outputFormat)->toBe('markdown')
        ->and($response->markdown)->toBe('# Parsed')
        ->and($response->pageCount)->toBe(3)
        ->and($response->runtime)->toBe(2.5)
        ->and($response->parseQualityScore)->toBe(4.2)
        ->and($response->parseQuality)->toBe(DatalabParseQuality::Excellent)
        ->and($response->checkpointId)->toBe('chk_1')
        ->and($response->isComplete())->toBeTrue()
        ->and($response->isSuccess())->toBeTrue();

    Http::assertSentCount(3);
    Http::assertSent(function (Request $request): bool {
        return $request->method() === 'GET'
            && $request->url() === 'https://www.datalab.to/api/v1/convert/req_sync_1'
            && $request->hasHeader('X-API-Key', 'test-api-key');
    });
});

it('parses paginated convert markdown and html into arrays', function () {
    Http::fake([
        'https://www.datalab.to/api/v1/convert/req_paginated' => Http::response([
            'status' => 'complete',
            'success' => true,
            'markdown' => "{0}------------------------------------------------\n# Page 1\nAlpha\n{1}------------------------------------------------\n# Page 2\nBeta",
            'html' => '<div class="page" data-page-id="0"><h1>Page 1</h1><p>Alpha</p></div><div class="page" data-page-id="1"><h1>Page 2</h1><p>Beta</p></div>',
        ], 200),
    ]);

    $response = Datalab::convert()->checkResult('req_paginated');

    expect($response)->toBeInstanceOf(ConvertResultResponse::class)
        ->and($response->parseQualityScore)->toBeNull()
        ->and($response->parseQuality)->toBeNull()
        ->and($response->markdownPaginated)->toBe([
            0 => "# Page 1\nAlpha",
            1 => "# Page 2\nBeta",
        ])
        ->and($response->htmlPaginated)->toBe([
            0 => '<h1>Page 1</h1><p>Alpha</p>',
            1 => '<h1>Page 2</h1><p>Beta</p>',
        ]);
});
