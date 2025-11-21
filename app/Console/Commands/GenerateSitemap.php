<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
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
    protected $description = 'Generate the sitemap for better SEO';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating sitemap...');

        // Use production URL from config
        $baseUrl = rtrim(config('app.url'), '/');

        $sitemap = Sitemap::create();

        // Add main public pages
        $sitemap->add(
            Url::create($baseUrl . '/')
                ->setLastModificationDate(now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(1.0)
        );

        // Add dashboard page (public)
        $sitemap->add(
            Url::create($baseUrl . '/dashboard')
                ->setLastModificationDate(now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_HOURLY)
                ->setPriority(0.9)
        );

        // Add cruise page
        $sitemap->add(
            Url::create($baseUrl . '/cruise')
                ->setLastModificationDate(now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(0.8)
        );

        // Add booking page
        $sitemap->add(
            Url::create($baseUrl . '/booking')
                ->setLastModificationDate(now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(0.8)
        );

        // Add entry conditions page
        $sitemap->add(
            Url::create($baseUrl . '/entry-conditions')
                ->setLastModificationDate(now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(0.8)
        );

        // Add branches page
        $sitemap->add(
            Url::create($baseUrl . '/branches')
                ->setLastModificationDate(now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(0.7)
        );

        // Add RSS feeds
        $sitemap->add(
            Url::create($baseUrl . '/feed/events/rss')
                ->setLastModificationDate(now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_HOURLY)
                ->setPriority(0.6)
        );

        $sitemap->add(
            Url::create($baseUrl . '/feed/events/atom')
                ->setLastModificationDate(now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_HOURLY)
                ->setPriority(0.6)
        );

        // Write the sitemap to public directory
        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap generated successfully at public/sitemap.xml');
        $this->info('Total URLs: ' . count($sitemap->getTags()));

        return Command::SUCCESS;
    }
}
