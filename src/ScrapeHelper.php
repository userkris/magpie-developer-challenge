<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\UriResolver;

class ScrapeHelper
{
    public static function fetchDocument(string $url, int $max_retries = 3, int $delay = 2): ?Crawler
    {
        $client = new Client();
        $attempt = 0;

        print('Trying to fetch: "' . $url . '"' . PHP_EOL);

        while ($attempt < $max_retries) {
            try {
                $response = $client->get($url);
                if ($response->getStatusCode() === 200) {
                    print('Success!' . PHP_EOL);
                    return new Crawler($response->getBody()->getContents(), $url);
                }
            } catch (RequestException $e) {
                $response = $e->getResponse();
                if ($response->getStatusCode() === 404) {
                    print('Request failed with status code ' . $response->getStatusCode() . ' "' . $url . '", skipping.' . PHP_EOL);
                    break;
                }
                print('Failed to fetch "'. $url .'", trying another attempt.' . PHP_EOL);
                $attempt++;
                sleep($delay);
            }
        }
        $attempt = 0;

        print('Failed to fetch: "' . $url . '"' . PHP_EOL);
        return null;
    }

    public static function getLinks (Crawler $document, string $selector): array
    {
        print('Getting pages with query: "' . $selector . '"' . PHP_EOL);
        return $document->filter($selector)->each(fn (Crawler $linkNode) => $linkNode->attr('href'));
    }

    public static function checkPagesCount (int $acquired, Crawler $document, string $selector = ''): bool
    {
        try {
            $textNode = $document->filter($selector)->text();
            $words = explode(' ', $textNode);
            $page_count = (int)$words[count($words) - 1];

            if (!$page_count) {
                throw new Exception('Failed to get page count from DOM or page count equals (int) 0.' . PHP_EOL);
            }
            print('Expected ' . $page_count . ' pages.' . PHP_EOL);

            return $acquired === $page_count;
        } catch (Exception $e) {
            print('Failed to get page count from entry document.' . PHP_EOL);
            return false;
        }
    }

    public static function getProductList (Crawler $document, string $selector = ''): array
    {
        try {
            $list = $document->filter($selector)->each(fn (Crawler $productNode) => $productNode);
            return $list;
        } catch (Exception $e) {
            print('Failed to scrape product list from DOM.');
            return [];
        }
    }

    public static function getTextNodeFromDom (Crawler $dom): ?string
    {
        if ($dom->count()) {
            return $dom->text();
        }
        return null;
    }
}
