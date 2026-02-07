<?php

namespace ImmiTranslate\Datalab\Commands;

use Illuminate\Console\Command;

class DatalabCommand extends Command
{
    public $signature = 'datalab-sdk-laravel';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
