<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use ImmiTranslate\Datalab\DTO\FormFillingResponse;
use ImmiTranslate\Datalab\DTO\FormFillingResultResponse;
use ImmiTranslate\Datalab\Facades\Datalab;
use ImmiTranslate\Datalab\FormField;

it('sends form filling request with fields and options', function () {
    Http::fake([
        '*' => Http::response([
            'request_id' => 'fill_req_1',
            'request_check_url' => 'https://www.datalab.to/api/v1/fill/fill_req_1',
            'success' => true,
            'error' => null,
            'versions' => [],
        ], 200),
    ]);

    $response = Datalab::formFilling()
        ->fields([
            new FormField(fieldKey: 'title', description: 'The title of the movie'),
            new FormField(fieldKey: 'director', description: 'The director of the movie'),
        ])
        ->webhookUrl('https://webhook.site/datalab-webhook')
        ->context('This is the form each Oscar nomination should fill out')
        ->confidenceThreshold(0.5)
        ->pageRange('1-15')
        ->skipCache()
        ->executeAsync();

    expect($response)->toBeInstanceOf(FormFillingResponse::class)
        ->and($response->isSuccess())->toBeTrue();

    Http::assertSent(function (Request $request): bool {
        $body = $request->body();

        return $request->method() === 'POST'
            && $request->url() === 'https://www.datalab.to/api/v1/fill'
            && $request->hasHeader('X-API-Key', 'test-api-key')
            && str_contains($body, 'name="field_data"')
            && str_contains($body, '"title":{"value":null,"description":"The title of the movie"}')
            && str_contains($body, '"director":{"value":null,"description":"The director of the movie"}')
            && str_contains($body, 'name="webhook_url"')
            && str_contains($body, 'https://webhook.site/datalab-webhook')
            && str_contains($body, 'name="context"')
            && str_contains($body, 'Oscar nomination')
            && str_contains($body, 'name="confidence_threshold"')
            && str_contains($body, '0.5')
            && str_contains($body, 'name="page_range"')
            && str_contains($body, '1-15')
            && str_contains($body, 'name="skip_cache"')
            && str_contains($body, 'true');
    });
});

it('maps form filling initial response dto', function () {
    Http::fake([
        '*' => Http::response([
            'request_id' => 'fill_req_2',
            'request_check_url' => 'https://www.datalab.to/api/v1/fill/fill_req_2',
            'success' => true,
            'error' => null,
            'versions' => ['api' => '2026.02.07'],
        ], 200),
    ]);

    $response = Datalab::formFilling()
        ->fieldData([
            'title' => [
                'value' => 'Inception',
                'description' => 'The movie title',
            ],
        ])
        ->executeAsync();

    expect($response)->toBeInstanceOf(FormFillingResponse::class)
        ->and($response->status)->toBe(200)
        ->and($response->requestId)->toBe('fill_req_2')
        ->and($response->requestCheckUrl)->toBe('https://www.datalab.to/api/v1/fill/fill_req_2')
        ->and($response->success)->toBeTrue()
        ->and($response->error)->toBeNull()
        ->and($response->versions)->toBe(['api' => '2026.02.07'])
        ->and($response->isSuccess())->toBeTrue();
});

it('execute polls form filling request check url until complete', function () {
    Http::fake([
        'https://www.datalab.to/api/v1/fill' => Http::response([
            'request_id' => 'fill_req_3',
            'request_check_url' => 'https://poll.test/fill/fill_req_3',
            'success' => true,
            'error' => null,
            'versions' => [],
        ], 200),
        'https://poll.test/fill/fill_req_3' => Http::sequence()
            ->push([
                'status' => 'processing',
                'success' => null,
                'error' => null,
            ], 200)
            ->push([
                'status' => 'complete',
                'success' => true,
                'error' => null,
                'output_format' => 'pdf',
                'output_base64' => 'JVBERi0xLjcK...',
                'fields_filled' => ['title', 'director'],
                'fields_not_found' => ['producer'],
                'runtime' => 3.42,
                'page_count' => 2,
                'cost_breakdown' => ['total' => 1],
                'versions' => ['fill' => '2026.02.07'],
            ], 200),
    ]);

    $response = Datalab::formFilling(0)
        ->fieldData([
            'title' => [
                'value' => 'Inception',
                'description' => 'The movie title',
            ],
        ])
        ->execute();

    expect($response)->toBeInstanceOf(FormFillingResultResponse::class)
        ->and($response->status)->toBe('complete')
        ->and($response->success)->toBeTrue()
        ->and($response->outputFormat)->toBe('pdf')
        ->and($response->outputBase64)->toBe('JVBERi0xLjcK...')
        ->and($response->fieldsFilled)->toBe(['title', 'director'])
        ->and($response->fieldsNotFound)->toBe(['producer'])
        ->and($response->runtime)->toBe(3.42)
        ->and($response->pageCount)->toBe(2)
        ->and($response->costBreakdown)->toBe(['total' => 1])
        ->and($response->versions)->toBe(['fill' => '2026.02.07'])
        ->and($response->isComplete())->toBeTrue()
        ->and($response->isSuccess())->toBeTrue()
        ->and($response->raw)->toHaveKey('fields_filled');

    Http::assertSentCount(3);
});

it('maps nullable result fields while processing', function () {
    Http::fake([
        '*' => Http::response([
            'status' => 'processing',
            'success' => null,
            'error' => null,
            'output_format' => null,
            'output_base64' => null,
            'fields_filled' => null,
            'fields_not_found' => null,
            'runtime' => null,
            'page_count' => null,
            'cost_breakdown' => [],
            'versions' => [],
        ], 200),
    ]);

    $response = Datalab::formFilling()->checkResultByUrl('https://poll.test/fill/processing');

    expect($response)->toBeInstanceOf(FormFillingResultResponse::class)
        ->and($response->status)->toBe('processing')
        ->and($response->fieldsFilled)->toBeNull()
        ->and($response->fieldsNotFound)->toBeNull()
        ->and($response->runtime)->toBeNull()
        ->and($response->pageCount)->toBeNull();
});

it('supports attaching a local file for form filling', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'fill-test-');
    file_put_contents($tempFile, 'sample-pdf-binary');

    Http::fake([
        '*' => Http::response([
            'request_id' => 'fill_req_4',
            'request_check_url' => 'https://www.datalab.to/api/v1/fill/fill_req_4',
            'success' => true,
            'error' => null,
            'versions' => [],
        ], 200),
    ]);

    Datalab::formFilling()
        ->fieldData([
            'title' => [
                'value' => 'Inception',
                'description' => 'The movie title',
            ],
        ])
        ->file($tempFile, 'test.pdf')
        ->executeAsync();

    Http::assertSent(function (Request $request): bool {
        $body = $request->body();

        return str_contains($body, 'name="file"; filename="test.pdf"')
            && str_contains($body, 'sample-pdf-binary');
    });

    @unlink($tempFile);

    expect(true)->toBeTrue();
});
