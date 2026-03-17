<?php

namespace Starlabs\LaravelPaysera\Commands;

use Illuminate\Console\Command;

class LaravelPayseraCommand extends Command
{
    public $signature = 'laravel-paysera';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
