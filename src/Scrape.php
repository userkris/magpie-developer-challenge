<?php

namespace App;

// error_reporting(E_ALL & ~E_DEPRECATED);

require 'vendor/autoload.php';

use App\Scraper;
use App\Product;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\UriResolver;

class ProductScrape extends Scraper
{
    public function scrapeProduct(Crawler $dom, array $output, ?string $url = null): array
    {
        $availability_text = ScrapeHelper::getTextNodeFromDom($dom->filter('div.my-4.text-sm.block.text-center'));
        $shipping_text = ScrapeHelper::getTextNodeFromDom($dom->filter('div.my-4.text-sm.block.text-center + div'));
        $variations = $dom->filter('img + div div')->children('span[data-colour]')->each(fn (Crawler $colorNode) => $colorNode->attr('data-colour'));
        $title = ScrapeHelper::getTextNodeFromDom($dom->filter('span.product-name'));
        $capacityMB = ScrapeHelper::getTextNodeFromDom($dom->filter('span.product-capacity'));
        $title_full = $title . ' ' . $capacityMB;

        foreach ($variations as $variation) {
            $product = new Product(
                title: $title_full,
                price: ScrapeHelper::getTextNodeFromDom($dom->filter('div.my-8.block.text-center.text-lg')),
                imageUrl: UriResolver::resolve($dom->filter('img')->attr('src'), $url ?: $dom->getUri()),
                capacityMB: $capacityMB,
                colour: $variation,
                availabilityText: $availability_text,
                isAvailable: $availability_text,
                shippingText: $shipping_text,
                shippingDate: $shipping_text
            );

            $product_exists = false;

            $product_to_add = $product->output();

            foreach ($output as $product_existed) {
                if ($product_to_add['title'] === $product_existed['title'] && $product_to_add['colour'] === $product_existed['colour']) {
                    $product_exists = true;
                    break;
                }
            }

            if (!$product_exists) {
                $output[] = $product_to_add;
            }
        }
        return $output;
    }
}

$scrape = new ProductScrape(
    url: 'https://www.magpiehq.com/developer-challenge/smartphones',
    look_for_pages_selector: '#pages a:not(.active)',
    url_is_product_page: true,
    look_for_pages_count_selector: '#products h2 + p.block.text-center.my-8',
    abort_on_page_count_mismatch: true,
    look_for_product_list_selector: '#products .product',
    url_force_directory: true,
);
$scrape->run();
