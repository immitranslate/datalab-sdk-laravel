<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use ImmiTranslate\Datalab\DTO\MarkerResponse;
use ImmiTranslate\Datalab\DTO\MarkerResultResponse;
use ImmiTranslate\Datalab\DTO\MarkerValidationDetail;
use ImmiTranslate\Datalab\Enums\DatalabMode;
use ImmiTranslate\Datalab\Enums\DatalabOutput;
use ImmiTranslate\Datalab\Facades\Datalab;

it('sends marker request with fluent api', function () {
    Http::fake([
        '*' => Http::response([
            'request_id' => 'req_123',
            'request_check_url' => 'https://www.datalab.to/api/v1/marker/req_123',
            'success' => true,
            'error' => null,
            'versions' => [],
        ], 200),
    ]);

    $response = Datalab::marker()
        ->fileUrl('https://r2.aws.com/test.pdf')
        ->mode(DatalabMode::Fast)
        ->outputFormat(DatalabOutput::Json)
        ->webhookUrl('https://testwebhook.com/test123')
        ->executeAsync();

    expect($response->isSuccess())->toBeTrue();

    Http::assertSent(function (Request $request): bool {
        $body = $request->body();

        return $request->method() === 'POST'
            && $request->url() === 'https://www.datalab.to/api/v1/marker'
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

it('supports multiple output formats and extras', function () {
    Http::fake([
        '*' => Http::response([
            'request_id' => 'req_456',
            'request_check_url' => 'https://www.datalab.to/api/v1/marker/req_456',
            'success' => true,
            'error' => null,
            'versions' => [],
        ], 200),
    ]);

    Datalab::marker()
        ->outputFormats(DatalabOutput::Markdown, DatalabOutput::Html)
        ->extras(['extract_links', 'new_block_types'])
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

it('maps a 200 marker response into dto', function () {
    Http::fake([
        '*' => Http::response([
            'request_id' => 'req_123',
            'request_check_url' => 'https://www.datalab.to/api/v1/marker/req_123',
            'success' => true,
            'error' => null,
            'versions' => [
                'marker' => '2026.02.07',
            ],
        ], 200),
    ]);

    $response = Datalab::marker()->executeAsync();

    expect($response)->toBeInstanceOf(MarkerResponse::class)
        ->and($response->status)->toBe(200)
        ->and($response->requestId)->toBe('req_123')
        ->and($response->requestCheckUrl)->toBe('https://www.datalab.to/api/v1/marker/req_123')
        ->and($response->success)->toBeTrue()
        ->and($response->error)->toBeNull()
        ->and($response->versions)->toBe(['marker' => '2026.02.07'])
        ->and($response->detail)->toBe([])
        ->and($response->isSuccess())->toBeTrue()
        ->and($response->isValidationError())->toBeFalse();
});

it('maps a 422 marker response into dto', function () {
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

    $response = Datalab::marker()->executeAsync();

    expect($response)->toBeInstanceOf(MarkerResponse::class)
        ->and($response->status)->toBe(422)
        ->and($response->requestId)->toBeNull()
        ->and($response->success)->toBeNull()
        ->and($response->detail)->toHaveCount(1)
        ->and($response->detail[0])->toBeInstanceOf(MarkerValidationDetail::class)
        ->and($response->detail[0]->loc)->toBe(['body', 'file_url'])
        ->and($response->detail[0]->msg)->toBe('Invalid file URL')
        ->and($response->detail[0]->type)->toBe('value_error.url')
        ->and($response->isSuccess())->toBeFalse()
        ->and($response->isValidationError())->toBeTrue();
});

it('execute sync polls marker result endpoint until complete', function () {
    Http::fake([
        'https://www.datalab.to/api/v1/marker' => Http::response([
            'request_id' => 'req_sync_1',
            'request_check_url' => 'https://www.datalab.to/api/v1/marker/req_sync_1',
            'success' => true,
            'error' => null,
            'versions' => [],
        ], 200),
        'https://www.datalab.to/api/v1/marker/req_sync_1' => Http::sequence()
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
                'versions' => ['marker' => '2026.02.07'],
            ], 200),
    ]);

    $response = Datalab::marker(0)->executeSync();

    expect($response)->toBeInstanceOf(MarkerResultResponse::class)
        ->and($response->status)->toBe('complete')
        ->and($response->outputFormat)->toBe('markdown')
        ->and($response->markdown)->toBe('# Parsed')
        ->and($response->pageCount)->toBe(3)
        ->and($response->runtime)->toBe(2.5)
        ->and($response->isComplete())->toBeTrue()
        ->and($response->isSuccess())->toBeTrue();

    Http::assertSentCount(3);
    Http::assertSent(function (Request $request): bool {
        return $request->method() === 'GET'
            && $request->url() === 'https://www.datalab.to/api/v1/marker/req_sync_1'
            && $request->hasHeader('X-API-Key', 'test-api-key');
    });
});
