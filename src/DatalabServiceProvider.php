<?php

namespace ImmiTranslate\Datalab;

use ImmiTranslate\Datalab\Commands\DatalabCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DatalabServiceProvider extends PackageServiceProvider
{
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
            ->hasViews()
            ->hasMigration('create_datalab_sdk_laravel_table')
            ->hasCommand(DatalabCommand::class);
    }
}
