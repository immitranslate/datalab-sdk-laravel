# Laravel SDK for Datalab API

[![Latest Version on Packagist](https://img.shields.io/packagist/v/immitranslate/datalab-sdk-laravel.svg?style=flat-square)](https://packagist.org/packages/immitranslate/datalab-sdk-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/immitranslate/datalab-sdk-laravel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/immitranslate/datalab-sdk-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/immitranslate/datalab-sdk-laravel/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/immitranslate/datalab-sdk-laravel/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/immitranslate/datalab-sdk-laravel.svg?style=flat-square)](https://packagist.org/packages/immitranslate/datalab-sdk-laravel)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require immitranslate/datalab-sdk-laravel
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="datalab-sdk-laravel-config"
```

This is the contents of the published config file:

```php
return [
    'endpoint' => env('DATALAB_ENDPOINT', 'https://www.datalab.to/api/v1/'),
    'api_key' => env('DATALAB_API_KEY'),
    'marker_poll_interval_seconds' => (int) env('DATALAB_MARKER_POLL_INTERVAL_SECONDS', 5),
    'extraction_schema_poll_interval_seconds' => (int) env('DATALAB_EXTRACTION_SCHEMA_POLL_INTERVAL_SECONDS', 5),
    'form_filling_poll_interval_seconds' => (int) env('DATALAB_FORM_FILLING_POLL_INTERVAL_SECONDS', 5),
    'supported_files' => [
        'mimetypes' => [
            'application/pdf',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel.sheet.macroEnabled.12',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
            'text/csv',
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.oasis.opendocument.text',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.oasis.opendocument.presentation',
            'text/html',
            'application/epub+zip',
            'image/png',
            'image/jpeg',
            'image/webp',
            'image/gif',
            'image/tiff',
        ],
        'extensions' => [
            'pdf',
            'xls',
            'xlsx',
            'xlsm',
            'xltx',
            'csv',
            'ods',
            'doc',
            'docx',
            'odt',
            'ppt',
            'pptx',
            'odp',
            'html',
            'epub',
            'png',
            'jpg',
            'jpeg',
            'webp',
            'gif',
            'tiff',
        ],
    ],
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="datalab-sdk-laravel-views"
```

## Usage

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

$schemaResponse = Datalab::generateSchemas()
    ->checkpoint('asdf123')
    ->webhookUrl('https://test.com') // optional; overrides account-level webhook for this request
    ->generate(); // alias of generateSync(), polls request_check_url

if ($schemaResponse->isSuccess()) {
    // $schemaResponse->suggestions['simple_schema'], moderate_schema, complex_schema
}

$fillResponse = Datalab::formFilling()
    ->fields([
        new FormField(fieldKey: 'title', description: 'The title of the movie'),
        new FormField(fieldKey: 'director', description: 'The name of the director of the movie'),
    ])
    ->webhookUrl('https://webhook.site/datalab-webhook') // optional; overrides account-level webhook for this request
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
