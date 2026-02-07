<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use ImmiTranslate\Datalab\DTO\ExtractionSchemaResponse;
use ImmiTranslate\Datalab\DTO\ExtractionSchemaResultResponse;
use ImmiTranslate\Datalab\Facades\Datalab;

it('sends extraction schema generation request', function () {
    Http::fake([
        '*' => Http::response([
            'request_id' => 'schema_req_1',
            'request_check_url' => 'https://www.datalab.to/api/v1/marker/extraction/gen_schemas/schema_req_1',
            'success' => true,
            'error' => null,
            'versions' => [],
        ], 200),
    ]);

    $response = Datalab::generateSchemas()
        ->checkpoint('asdf123')
        ->webhookUrl('https://test.com')
        ->generateAsync();

    expect($response)->toBeInstanceOf(ExtractionSchemaResponse::class)
        ->and($response->requestId)->toBe('schema_req_1')
        ->and($response->isSuccess())->toBeTrue();

    Http::assertSent(function (Request $request): bool {
        $body = $request->body();

        return $request->method() === 'POST'
            && $request->url() === 'https://www.datalab.to/api/v1/marker/extraction/gen_schemas'
            && $request->hasHeader('X-API-Key', 'test-api-key')
            && str_contains($body, '"checkpoint_id":"asdf123"')
            && str_contains($body, '"webhook_url":"https:\\/\\/test.com"');
    });
});

it('maps extraction schema initial response dto', function () {
    Http::fake([
        '*' => Http::response([
            'request_id' => 'schema_req_2',
            'request_check_url' => 'https://www.datalab.to/api/v1/marker/extraction/gen_schemas/schema_req_2',
            'success' => true,
            'error' => null,
            'versions' => ['api' => '2026.02.07'],
        ], 200),
    ]);

    $response = Datalab::generateSchemas()
        ->checkpoint('asdf123')
        ->generateAsync();

    expect($response)->toBeInstanceOf(ExtractionSchemaResponse::class)
        ->and($response->status)->toBe(200)
        ->and($response->requestId)->toBe('schema_req_2')
        ->and($response->requestCheckUrl)->toBe('https://www.datalab.to/api/v1/marker/extraction/gen_schemas/schema_req_2')
        ->and($response->success)->toBeTrue()
        ->and($response->error)->toBeNull()
        ->and($response->versions)->toBe(['api' => '2026.02.07'])
        ->and($response->isSuccess())->toBeTrue();
});

it('generate polls request check url until complete', function () {
    Http::fake([
        'https://www.datalab.to/api/v1/marker/extraction/gen_schemas' => Http::response([
            'request_id' => 'schema_req_3',
            'request_check_url' => 'https://poll.test/status/schema_req_3',
            'success' => true,
            'error' => null,
            'versions' => [],
        ], 200),
        'https://poll.test/status/schema_req_3' => Http::sequence()
            ->push([
                'success' => null,
                'error' => null,
                'suggestions' => null,
                'status' => 'processing',
                'page_count' => null,
                'total_cost' => null,
            ], 200)
            ->push([
                'success' => true,
                'error' => null,
                'suggestions' => [
                    'simple_schema' => '{"type":"object"}',
                    'moderate_schema' => '{"type":"object"}',
                    'complex_schema' => '{"type":"object"}',
                ],
                'status' => 'complete',
                'page_count' => 1,
                'total_cost' => 1,
            ], 200),
    ]);

    $response = Datalab::generateSchemas(0)
        ->checkpoint('asdf123')
        ->webhookUrl('https://test.com')
        ->generate();

    expect($response)->toBeInstanceOf(ExtractionSchemaResultResponse::class)
        ->and($response->status)->toBe('complete')
        ->and($response->success)->toBeTrue()
        ->and($response->pageCount)->toBe(1)
        ->and($response->totalCost)->toBe(1)
        ->and($response->suggestions)->not->toBeNull()
        ->and($response->suggestions)->toHaveKeys(['simple_schema', 'moderate_schema', 'complex_schema'])
        ->and($response->isComplete())->toBeTrue()
        ->and($response->isSuccess())->toBeTrue();

    Http::assertSentCount(3);
    Http::assertSent(function (Request $request): bool {
        return $request->method() === 'GET'
            && $request->url() === 'https://poll.test/status/schema_req_3'
            && $request->hasHeader('X-API-Key', 'test-api-key');
    });
});
