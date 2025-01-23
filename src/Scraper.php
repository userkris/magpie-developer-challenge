<?php

namespace App;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\UriResolver;

class Scraper
{
    private array $output = [];

    private array $pages = [];

    private int $pages_count = 0;

    private array $products = [];

    private string $url = '';

    private bool $url_is_product_page = true;

    private ?string $look_for_pages_selector = '';

    private ?string $look_for_pages_count_selector = '';

    private bool $abort_on_page_count_mismatch = true;

    private ?string $look_for_product_list_selector = '';

    private bool $url_force_directory = false;

    public function __construct (
        string $url,
        string $look_for_pages_selector = '',
        bool $url_is_product_page = true,
        string $look_for_pages_count_selector = '',
        bool $abort_on_page_count_mismatch = true,
        string $look_for_product_list_selector = '',
        bool $url_force_directory = false
    )
    {
        $this->url = $url;
        $this->look_for_pages_selector = $look_for_pages_selector;
        $this->url_is_product_page = $url_is_product_page;
        $this->look_for_pages_count_selector = $look_for_pages_count_selector;
        $this->abort_on_page_count_mismatch = $abort_on_page_count_mismatch;
        $this->look_for_product_list_selector = $look_for_product_list_selector;
        $this->url_force_directory = $url_force_directory;
    }

    final public function run(): void
    {
        # prepare url
        if ($this->url_force_directory) {
            if (substr($this->url, -1) !== '/') {
                $this->url = $this->url . '/';
            }
        }

        # fetch html
        $entry_document = ScrapeHelper::fetchDocument($this->url);

        if (!$entry_document) {
            print('Nothing to scrape. Abort.' . PHP_EOL);
            die();
        }

        # puts initial document in scraping plan as it has product data
        if ($this->url_is_product_page) {
            $this->pages[$this->url] = $entry_document;
        }

        # if selector exists, look for pages
        if ($this->look_for_pages_selector) {
            $links_to_fetch = ScrapeHelper::getLinks($entry_document, $this->look_for_pages_selector);

            foreach ($links_to_fetch as $link) {
                $uri = UriResolver::resolve($link, $this->url);
                $document = ScrapeHelper::fetchDocument($uri);
                if ($document) {
                    $this->pages[$uri] = $document;
                } else {
                    print('Failed at fetching "' . $uri . '", skipping.' . PHP_EOL);
                }
            }
        }

        # Check page number and abort if nothing to scrape
        $this->pages_count = count($this->pages);
        print(PHP_EOL);
        print('Acquired ' . $this->pages_count . ' pages.' . PHP_EOL);
        if ($this->look_for_pages_count_selector) {
            $page_count_match = ScrapeHelper::checkPagesCount($this->pages_count, $entry_document, $this->look_for_pages_count_selector);
            if (!$page_count_match && $this->abort_on_page_count_mismatch) {
                print('Page count does not match acquired page count. Abort.' . PHP_EOL);
                die();
            }
        }
        if (count($this->pages) < 1) {
            print('Nothing to scrape. Abort.' . PHP_EOL);
            die();
        }

        # Scrape all pages for products
        foreach ($this->pages as $page) {
            $this->products = array_merge($this->products, ScrapeHelper::getProductList($page, $this->look_for_product_list_selector));
        }
        print('Found ' . count($this->products) . ' product entries in DOM across all pages.' . PHP_EOL);

        # Scrape product entries
        foreach ($this->products as $product_entry_dom) {
            $this->output = $this->scrapeProduct($product_entry_dom, output: $this->output, url: $this->url);
        }
        print(PHP_EOL);
        print('Found ' . count($this->output) . ' products. (with variations, duplicates removed)' . PHP_EOL);

        # Dump
        $this->dumpToJson();
    }

    private function dumpToJson (): void
    {
        $date = date('Y-m-d', strtotime('now'));
        $filename = 'output-' . $date . '.json';
        file_put_contents($filename, json_encode($this->output, JSON_PRETTY_PRINT));
        print('Dump created in ' . $filename);
    }

    public function scrapeProduct(Crawler $dom, array $output): array
    {
        // extend with a child class
    }

}

