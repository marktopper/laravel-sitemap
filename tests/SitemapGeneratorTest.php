<?php

namespace Spatie\Sitemap\Test;

use Throwable;
use Spatie\Sitemap\Tags\Url;
use Psr\Http\Message\UriInterface;
use Spatie\Sitemap\SitemapGenerator;

class SitemapGeneratorTest extends TestCase
{
    /** @var \Spatie\Sitemap\SitemapGenerator */
    protected $sitemapGenerator;

    public function setUp()
    {
        $this->checkIfTestServerIsRunning();

        parent::setUp();
    }

    /** @test */
    public function it_can_generate_a_sitemap()
    {
        $sitemapPath = $this->temporaryDirectory->path('test.xml');

        SitemapGenerator::create('http://localhost:4020')
            ->writeToFile($sitemapPath);

        $this->assertMatchesXmlSnapshot(file_get_contents($sitemapPath));
    }

    /** @test */
    public function it_can_modify_the_attributes_while_generating_the_sitemap()
    {
        $sitemapPath = $this->temporaryDirectory->path('test.xml');

        SitemapGenerator::create('http://localhost:4020')
            ->hasCrawled(function (Url $url) {
                if ($url->segment(1) === 'page3') {
                    $url->setPriority(0.6);
                }

                return $url;
            })
            ->writeToFile($sitemapPath);

        $this->assertMatchesXmlSnapshot(file_get_contents($sitemapPath));
    }

    /** @test */
    public function it_will_not_add_the_url_to_the_site_map_if_has_crawled_does_not_return_it()
    {
        $sitemapPath = $this->temporaryDirectory->path('test.xml');

        SitemapGenerator::create('http://localhost:4020')
            ->hasCrawled(function (Url $url) {
                if ($url->segment(1) === 'page3') {
                    return;
                }

                return $url;
            })
            ->writeToFile($sitemapPath);

        $this->assertMatchesXmlSnapshot(file_get_contents($sitemapPath));
    }

    /** @test */
    public function it_will_not_crawl_an_url_if_should_crawl_returns_false()
    {
        $sitemapPath = $this->temporaryDirectory->path('test.xml');

        SitemapGenerator::create('http://localhost:4020')
            ->shouldCrawl(function (UriInterface $url) {
                return ! strpos($url->getPath(), 'page3');
            })
            ->writeToFile($sitemapPath);

        $this->assertMatchesXmlSnapshot(file_get_contents($sitemapPath));
    }

    /** @test */
    public function it_can_use_a_custom_profile()
    {
        config(['sitemap.crawl_profile' => CustomCrawlProfile::class]);

        $sitemapPath = $this->temporaryDirectory->path('test.xml');

        SitemapGenerator::create('http://localhost:4020')
            ->writeToFile($sitemapPath);

        $this->assertMatchesXmlSnapshot(file_get_contents($sitemapPath));
    }

    protected function checkIfTestServerIsRunning()
    {
        try {
            file_get_contents('http://localhost:4020');
        } catch (Throwable $e) {
            $this->handleTestServerNotRunning();
        }
    }

    protected function handleTestServerNotRunning()
    {
        if (getenv('TRAVIS')) {
            $this->fail('The test server is not running on Travis.');
        }

        $this->markTestSkipped('The test server is not running.');
    }
}
