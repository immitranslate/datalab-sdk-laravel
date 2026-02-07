<?php

namespace ImmiTranslate\Datalab\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use ImmiTranslate\Datalab\DatalabServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'ImmiTranslate\\Datalab\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            DatalabServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('datalab-sdk-laravel.endpoint', 'https://www.datalab.to/api/v1/');
        config()->set('datalab-sdk-laravel.api_key', 'test-api-key');
        config()->set('datalab-sdk-laravel.marker_poll_interval_seconds', 5);

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }
}
