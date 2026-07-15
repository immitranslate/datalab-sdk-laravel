<?php

namespace ImmiTranslate\Datalab;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DatalabServiceProvider extends PackageServiceProvider
{
    public function registeringPackage(): void
    {
        $this->app->singleton('datalab', function (): DatalabClient {
            return new DatalabClient(
                endpoint: (string) config('datalab-sdk-laravel.endpoint'),
                apiKey: (string) config('datalab-sdk-laravel.api_key'),
                markerPollIntervalSeconds: (int) config('datalab-sdk-laravel.marker_poll_interval_seconds', 5),
                extractionSchemaPollIntervalSeconds: (int) config('datalab-sdk-laravel.extraction_schema_poll_interval_seconds', 5),
                formFillingPollIntervalSeconds: (int) config('datalab-sdk-laravel.form_filling_poll_interval_seconds', 5),
                convertPollIntervalSeconds: (int) config('datalab-sdk-laravel.convert_poll_interval_seconds', 5),
            );
        });
    }

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('datalab-sdk-laravel')
            ->hasConfigFile()
            ->hasViews();
    }
}
