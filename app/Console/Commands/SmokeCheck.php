<?php

namespace App\Console\Commands;

use App\Http\Controllers\Site\ToolsController;
use App\Support\Guides;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Throwable;

/**
 * Requests the main pages through the application, as the web server would, and
 * reports any error. Run by deploy.sh after each install or update.
 */
class SmokeCheck extends Command
{
    protected $signature = 'immobooking:check';

    protected $description = 'Check that the main pages answer without error';

    public function handle(Kernel $kernel): int
    {
        $paths = ['/admin/login', '/robots.txt'];
        $failures = 0;

        if (config('immobooking.website')) {
            $paths = [...$paths, '/', '/guides', '/outils', '/sitemap.xml', '/mentions-legales', '/confidentialite'];
            foreach (array_keys(ToolsController::TOOLS) as $slug) {
                $paths[] = '/outils/'.$slug;
            }
            try {
                if ($guide = Guides::all()->first()) {
                    $paths[] = '/guides/'.$guide['slug'];
                }
            } catch (Throwable $e) {
                $failures++;
                $this->error('✗ guides (resources/content/guides) : '.$e->getMessage());
            }
        }

        if (config('immobooking.demo.enabled')) {
            $paths[] = '/demo';
        }

        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        foreach ($paths as $path) {
            try {
                $request = Request::create($path, 'GET', server: ['HTTPS' => 'on', 'HTTP_HOST' => $host]);
                $response = $kernel->handle($request);
                $kernel->terminate($request, $response);
                $status = $response->getStatusCode();
                $error = $response->exception ?? null;
            } catch (Throwable $e) {
                $status = 500;
                $error = $e;
            }

            if ($status >= 400) {
                $failures++;
                $this->error("✗ {$path} → {$status}".($error ? ' : '.$error->getMessage() : ''));
            } else {
                $this->line("✓ {$path} → {$status}");
            }
        }

        if ($failures > 0) {
            $this->error("{$failures} page(s) en erreur.");

            return self::FAILURE;
        }

        $this->info('Toutes les pages répondent.');

        return self::SUCCESS;
    }
}
