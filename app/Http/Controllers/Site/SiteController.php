<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Support\Demo;
use App\Support\Guides;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

/** Marketing website: landing page, demo, guides, legal pages, sitemap and robots.txt. */
class SiteController extends Controller
{
    public function home(): View
    {
        return view('site.home', ['guides' => Guides::all()->take(3)]);
    }

    public function demo(): View|RedirectResponse
    {
        abort_unless(Demo::enabled(), 404);

        Demo::resetIfStale();

        $properties = Property::query()
            ->whereBelongsTo(Demo::agent(), 'agent')
            ->withCount(['slots as available_slots_count' => fn ($q) => $q->available()])
            ->orderBy('created_at')
            ->get();

        return view('site.demo', ['properties' => $properties]);
    }

    public function guides(): View
    {
        return view('site.guides', ['guides' => Guides::all()]);
    }

    public function guide(string $slug): View
    {
        $guide = Guides::find($slug);
        abort_if($guide === null, 404);

        return view('site.guide', [
            'guide' => $guide,
            'others' => Guides::all()->where('slug', '!=', $slug)->take(3),
        ]);
    }

    public function legal(): View
    {
        return view('site.legal');
    }

    public function privacy(): View
    {
        return view('site.privacy');
    }

    public function sitemap(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'lastmod' => null],
            ['loc' => route('site.guides'), 'lastmod' => Guides::all()->max('updated')],
            ['loc' => route('site.tools'), 'lastmod' => null],
        ]);

        foreach (array_keys(ToolsController::TOOLS) as $slug) {
            $urls->push(['loc' => route('site.tool.'.$slug), 'lastmod' => null]);
        }

        if (Demo::enabled()) {
            $urls->push(['loc' => route('site.demo'), 'lastmod' => null]);
        }

        Guides::all()->each(fn (array $guide) => $urls->push([
            'loc' => route('site.guide', $guide['slug']),
            'lastmod' => $guide['updated'],
        ]));

        $urls->push(['loc' => route('site.legal'), 'lastmod' => null], ['loc' => route('site.privacy'), 'lastmod' => null]);

        return response()
            ->view('site.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $lines = config('immobooking.website')
            ? [
                'User-agent: *',
                'Disallow: /admin',
                'Disallow: /approve/',
                'Disallow: /rdv/',
                'Disallow: /v/',
                'Disallow: /calendar/',
                'Disallow: /suivi/',
                'Disallow: /affiche/',
                'Disallow: /bon-de-visite/',
                'Disallow: /outils/generateur-qr-code/qr-code.svg',
                'Disallow: /livewire/',
                '',
                'Sitemap: '.route('site.sitemap'),
            ]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
