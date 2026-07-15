# Laravel SDK for Datalab API

[![Latest Version on Packagist](https://img.shields.io/packagist/v/immitranslate/datalab-sdk-laravel.svg?style=flat-square)](https://packagist.org/packages/immitranslate/datalab-sdk-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/immitranslate/datalab-sdk-laravel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/immitranslate/datalab-sdk-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/immitranslate/datalab-sdk-laravel/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/immitranslate/datalab-sdk-laravel/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/immitranslate/datalab-sdk-laravel.svg?style=flat-square)](https://packagist.org/packages/immitranslate/datalab-sdk-laravel)

To obtain an API key for Datalab, go to the [API keys](https://www.datalab.to/app/keys) page once you've created an account.

## Installation

You can install the package via composer:

```bash
composer require immitranslate/datalab-sdk-laravel
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="datalab-sdk-laravel-config"
```

## Usage

### Convert API

```php
use ImmiTranslate\Datalab\Enums\DatalabExtra;
use ImmiTranslate\Datalab\Enums\DatalabMode;
use ImmiTranslate\Datalab\Enums\DatalabOutput;
use ImmiTranslate\Datalab\Facades\Datalab;

$response = Datalab::convert()
    ->fileUrl('https://r2.aws.com/test.pdf') // or ->file('/absolute/path/to/document.pdf')
    ->mode(DatalabMode::Fast)
    ->outputFormats(DatalabOutput::Markdown, DatalabOutput::Json)
    ->extras([DatalabExtra::ExtractLinks, DatalabExtra::ChartUnderstanding]) // optional feature flags
    ->tokenEfficientMarkdown() // optional; compact markdown for LLM usage
    ->saveCheckpoint() // optional; checkpoint_id can be reused by /extract or /segment
    ->webhookUrl('https://testwebhook.com/test123') // optional; overrides account-level webhook for this request
    ->execute(); // alias of executeSync(), polls /convert/{request_id}

if ($response->isSuccess()) {
    // $response->markdown, $response->html, $response->json, $response->chunks
    // $response->checkpointId when saveCheckpoint() was used
}

// If you only want the initial request_id response (no polling):
$queued = Datalab::convert()->executeAsync();
// $queued->requestId, $queued->requestCheckUrl, $queued->isValidationError()
```

> **File limits:** maximum file size is 200 MB, with up to 7,000 pages per request. Results are deleted from Datalab servers one hour after processing completes — retrieve them promptly.

#### Convert with high accuracy

```php
$result = Datalab::convert()
    ->file('/absolute/path/to/complex_document.pdf')
    ->mode(DatalabMode::Accurate)
    ->execute();

echo $result->parseQualityScore; // 0-5
echo $result->markdown;
```

#### Quality gates with the parse quality score

Every conversion includes a `parseQualityScore` (0-5), exposed alongside a `parseQuality` enum band (`Excellent` 4.0-5.0, `Good` 3.0-3.9, `Fair` 2.0-2.9, `Poor` 0.0-1.9):

```php
use ImmiTranslate\Datalab\Enums\DatalabParseQuality;

$result = Datalab::convert()
    ->file('/absolute/path/to/document.pdf')
    ->mode(DatalabMode::Balanced)
    ->execute();

if (in_array($result->parseQuality, [DatalabParseQuality::Fair, DatalabParseQuality::Poor], true)) {
    // Retry with higher accuracy
    $result = Datalab::convert()
        ->file('/absolute/path/to/document.pdf')
        ->mode(DatalabMode::Accurate)
        ->execute();
}

$result->parseQuality?->recommendedAction(); // e.g. "Use the output directly"
```

#### HTML with block IDs for citations

```php
$result = Datalab::convert()
    ->fileUrl('https://example.com/document.pdf')
    ->outputFormat(DatalabOutput::Html)
    ->addBlockIds()
    ->execute();
// HTML elements carry data-block-id attributes for citation tracking
```

#### Bounding box add-ons

Billed at $0.30 per 1K pages each; require the `html` output format and also enable word bboxes:

```php
$result = Datalab::convert()
    ->file('/absolute/path/to/document.pdf')
    ->outputFormat(DatalabOutput::Html)
    ->extras([DatalabExtra::TableCellBboxes, DatalabExtra::ListItemBboxes])
    ->execute();
// HTML contains data-bbox and data-confidence on table cells, list items, and words
```

#### Process specific pages (or spreadsheet sheets)

```php
$result = Datalab::convert()
    ->file('/absolute/path/to/large_document.pdf')
    ->pageRange('0-4,10,15-20') // pages 0-4, 10, and 15-20 (0-indexed)
    ->execute();

// For spreadsheet files, pageRange() filters by sheet index (0-based):
$sheets = Datalab::convert()
    ->file('/absolute/path/to/workbook.xlsx')
    ->pageRange('0,2') // first and third sheets only
    ->execute();
```

#### Extract track changes from Word documents

```php
$result = Datalab::convert()
    ->file('/absolute/path/to/document_with_changes.docx')
    ->extras(DatalabExtra::TrackChanges)
    ->outputFormat(DatalabOutput::Json)
    ->execute();
```

#### Additional config

```php
$result = Datalab::convert()
    ->file('/absolute/path/to/workbook.xlsx')
    ->additionalConfig([
        'keep_spreadsheet_formatting' => true,
        'keep_pageheader_in_output' => false,
    ])
    ->execute();
```

#### Checkpoints

Save a processing checkpoint to reuse parsed results without re-processing:

```php
// Step 1: convert and save a checkpoint
$result = Datalab::convert()
    ->file('/absolute/path/to/document.pdf')
    ->saveCheckpoint()
    ->execute();

// Step 2: reuse the parsed document, e.g. for schema generation
$schemas = Datalab::generateSchemas()
    ->checkpoint($result->checkpointId)
    ->generate();
```

### Marker API

> **Deprecated:** Datalab is deprecating the Marker API. Use the [Convert API](#convert-api) instead.

```php
use ImmiTranslate\Datalab\Enums\DatalabMode;
use ImmiTranslate\Datalab\Enums\DatalabOutput;
use ImmiTranslate\Datalab\Facades\Datalab;
use ImmiTranslate\Datalab\FormField;

$response = Datalab::marker()
    ->fileUrl('https://r2.aws.com/test.pdf')
    ->mode(DatalabMode::Fast)
    ->outputFormat(DatalabOutput::Json)
    ->webhookUrl('https://testwebhook.com/test123') // optional; overrides account-level webhook for this request
    ->execute(); // alias of executeSync(), polls /marker/{request_id}

if ($response->isSuccess()) {
    // $response->markdown, $response->html, $response->json, $response->chunks
}

// If you only want the initial request_id response (no polling):
$queued = Datalab::marker()->executeAsync();
// $queued->requestId, $queued->requestCheckUrl, $queued->isValidationError()
```

### Schema API

```php
use ImmiTranslate\Datalab\Facades\Datalab;

$schemaResponse = Datalab::generateSchemas()
    ->checkpoint('asdf123')
    ->webhookUrl('https://test.com') // optional; overrides account-level webhook for this request
    ->generate(); // alias of generateSync(), polls request_check_url

if ($schemaResponse->isSuccess()) {
    // $schemaResponse->suggestions['simple_schema'], moderate_schema, complex_schema
}
```

### Form Filling API

```php
use ImmiTranslate\Datalab\Facades\Datalab;
use ImmiTranslate\Datalab\FormField;

$fillResponse = Datalab::formFilling()
    ->fields([
        new FormField(fieldKey: 'title', description: 'The title of the movie'),
        new FormField(fieldKey: 'director', description: 'The name of the director of the movie'),
    ])
    ->context('This is the form each Oscar nomination should fill out')
    ->confidenceThreshold(0.5)
    ->pageRange('1-15')
    ->file('/absolute/path/to/form.pdf') // optional local file upload
    ->execute(); // alias of executeSync(), polls request_check_url

if ($fillResponse->isSuccess()) {
    // Access full payload via $fillResponse->raw
}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Ian Hawes](https://github.com/ianhawes)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
