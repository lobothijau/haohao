<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Models\Series;
use App\Models\Story;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the sitemap for the application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sitemap = Sitemap::create();

        $sitemap->add(Url::create('/'));
        $sitemap->add(Url::create('/series'));
        $sitemap->add(Url::create('/blog'));
        $sitemap->add(Url::create('/membership'));

        Story::query()
            ->where('is_published', true)
            ->select(['slug', 'updated_at'])
            ->each(function (Story $story) use ($sitemap) {
                $sitemap->add(
                    Url::create("/stories/{$story->slug}")
                        ->setLastModificationDate($story->updated_at)
                );
            });

        Series::query()
            ->where('is_published', true)
            ->select(['slug', 'updated_at'])
            ->each(function (Series $series) use ($sitemap) {
                $sitemap->add(
                    Url::create("/series/{$series->slug}")
                        ->setLastModificationDate($series->updated_at)
                );
            });

        BlogPost::query()
            ->where('is_published', true)
            ->select(['slug', 'updated_at'])
            ->each(function (BlogPost $post) use ($sitemap) {
                $sitemap->add(
                    Url::create("/blog/{$post->slug}")
                        ->setLastModificationDate($post->updated_at)
                );
            });

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap generated successfully.');

        return self::SUCCESS;
    }
}
