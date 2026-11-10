<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

/**
 * SEO guides, written as Markdown files in resources/content/guides/{slug}.md
 * with a YAML front matter (title, description, date, updated).
 * Adding a guide = adding a file: it appears in /guides and in the sitemap.
 */
class Guides
{
    /**
     * @return Collection<int, array{slug: string, title: string, description: string, date: Carbon, updated: Carbon, html: string, minutes: int}>
     */
    public static function all(): Collection
    {
        $directory = resource_path('content/guides');

        $signature = collect(File::glob($directory.'/*.md'))
            ->map(fn (string $file) => $file.filemtime($file))
            ->implode('|');

        return Cache::rememberForever('guides:'.md5($signature), fn () => collect(File::glob($directory.'/*.md'))
            ->map(fn (string $file) => static::parse($file))
            ->sortByDesc('date')
            ->values());
    }

    /**
     * @return array{slug: string, title: string, description: string, date: Carbon, updated: Carbon, html: string, minutes: int}|null
     */
    public static function find(string $slug): ?array
    {
        return static::all()->firstWhere('slug', $slug);
    }

    /**
     * @return array{slug: string, title: string, description: string, date: Carbon, updated: Carbon, html: string, minutes: int}
     */
    private static function parse(string $file): array
    {
        $environment = new Environment([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
            'heading_permalink' => [],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new FrontMatterExtension);
        $environment->addExtension(new TableExtension);

        $result = (new MarkdownConverter($environment))->convert(File::get($file));
        $meta = $result instanceof RenderedContentWithFrontMatter ? $result->getFrontMatter() : [];
        $html = (string) $result->getContent();
        $date = Carbon::parse($meta['date'] ?? filemtime($file));

        return [
            'slug' => basename($file, '.md'),
            'title' => (string) $meta['title'],
            'description' => (string) $meta['description'],
            'date' => $date,
            'updated' => isset($meta['updated']) ? Carbon::parse($meta['updated']) : $date,
            'html' => $html,
            'minutes' => max(1, (int) round(str_word_count(strip_tags($html)) / 220)),
        ];
    }
}
