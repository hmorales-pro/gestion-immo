<?php

namespace App\Console\Commands;

use App\Support\Demo;
use Illuminate\Console\Command;

class ResetDemo extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Recreate the public demo data (agent, properties, slots, requests)';

    public function handle(): int
    {
        if (! Demo::enabled()) {
            $this->error('The demo is disabled (IMMOBOOKING_DEMO=false).');

            return self::FAILURE;
        }

        Demo::reset();
        $this->info('Demo reset. Log in with '.config('immobooking.demo.email').' / '.config('immobooking.demo.password'));

        return self::SUCCESS;
    }
}
