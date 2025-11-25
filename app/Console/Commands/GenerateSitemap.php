<?php
//php artisan sitemap:generate --output=public/sitemap.xml
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Webkul\Sitemap\Models\Category;
use Webkul\Sitemap\Models\Product;
use Webkul\Sitemap\Models\Page;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate {--output=public/sitemap.xml}';

    protected $description = 'Generate sitemap.xml';

    public function handle()
    {
        $outputOption = $this->option('output') ?? 'public/sitemap.xml';
        $path = $this->resolveOutputPath($outputOption);

        $sitemap = Sitemap::create();

        $this->info('Adding homepage');
        $homeUrl = url('/');
        if (! str_contains($homeUrl, '/root')) {
            $sitemap->add(Url::create($homeUrl));
        }

        $this->info('Adding categories');
        Category::query()->chunk(500, function ($items) use ($sitemap) {
            foreach ($items as $item) {
                $tag = $item->toSitemapTag();
                if (! empty($tag)) {
                    $url = $this->getTagUrl($tag);
                    if ($url && str_contains($url, '/root')) {
                        continue;
                    }
                    $sitemap->add($tag);
                }
            }
        });

        $this->info('Adding products');
        Product::query()->chunk(500, function ($items) use ($sitemap) {
            foreach ($items as $item) {
                $tag = $item->toSitemapTag();
                if (! empty($tag)) {
                    $url = $this->getTagUrl($tag);
                    if ($url && str_contains($url, '/root')) {
                        continue;
                    }
                    $sitemap->add($tag);
                }
            }
        });

        $this->info('Adding pages');
        Page::query()->chunk(500, function ($items) use ($sitemap) {
            foreach ($items as $item) {
                $tag = $item->toSitemapTag();
                if (! empty($tag)) {
                    $url = $this->getTagUrl($tag);
                    if ($url && str_contains($url, '/root')) {
                        continue;
                    }
                    $sitemap->add($tag);
                }
            }
        });

        $dir = dirname($path);
        if (! is_dir($dir)) {
            if (! mkdir($dir, 0755, true) && ! is_dir($dir)) {
                $this->error('Failed to create directory: ' . $dir);
                return Command::FAILURE;
            }
        }

        $xmlString = $sitemap->render();

        $formatted = $this->formatXml($xmlString);

        file_put_contents($path, $formatted);

        $this->info('Sitemap generated: ' . $path);

        return Command::SUCCESS;
    }

    protected function resolveOutputPath(string $output): string
    {
        if (str_starts_with($output, 'public/')) {
            return public_path(substr($output, 7));
        }

        if (str_starts_with($output, 'storage/')) {
            return storage_path(substr($output, 8));
        }

        if ($output[0] === DIRECTORY_SEPARATOR) {
            return $output;
        }

        return base_path($output);
    }

    protected function formatXml(string $xml): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml);
        return $dom->saveXML();
    }

    protected function getTagUrl($tag): ?string
    {
        if ($tag instanceof Url) {
            $u = $tag->url;
            if (is_string($u)) {
                return $u;
            }
            if (is_object($u)) {
                return (string) $u;
            }
            return null;
        }

        if (is_string($tag)) {
            return $tag;
        }

        return null;
    }
}
