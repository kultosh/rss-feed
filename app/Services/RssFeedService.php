<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use SimpleXMLElement;
use Exception;
use Illuminate\Support\Facades\Log;

class RssFeedService
{
    /**
     * Fetches the RSS feed for the specified section.
     * This method attempts to retrieve the RSS feed for the given section by calling the `getRssFeedFromApi` method.
     * @param string $section The section for which to fetch the RSS feed.
     * @return string Returns the RSS feed as XML if the fetch is successful.
     * @throws \Exception If the RSS feed cannot be fetched from the API.
     */
    public function fetchRssFeed(string $section)
    {
        $rssFeed = $this->getRssFeedFromApi($section);

        if (!$rssFeed) {
            throw new Exception('Unable to fetch RSS feed');
        }

        return $rssFeed;
    }
    
    /**
     * Retrieves the RSS feed data from the Guardian API.
     * This method sends an HTTP GET request to the Guardian API to fetch data for the specified section.
     * If the request fails, it returns null. Otherwise, it processes the response and converts it into RSS format.
     * @param string $section The section for which to fetch the RSS feed data.
     * @return string|null Returns the RSS feed as XML if the API request is successful or null if the request fails.
     */
    private function getRssFeedFromApi(string $section)
    {
        $response = Http::get(env('GUARDAIN_URL').'sections', [
            'q' => $section,
            'api-key' => env('GUARDAIN_API_KEY'),
            'format' => 'json',
        ]);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();
        Log::info('Response from GUARDAIN', ['data' => $data]);
        return $this->convertToRss($data);
    }

    /**
     * Converts the API response data into an RSS feed format.
     * This method takes the raw JSON data from the Guardian API and converts it into an RSS 2.0 XML format.
     * Each article in the response is represented as an RSS item, with the title and link of the article.
     * @param array $data The data returned by the API in JSON format.
     * @return string Returns the RSS feed as XML.
     */
    private function convertToRss(array $data)
    {
        $rss = new SimpleXMLElement('<rss/>');
        $rss->addAttribute('version', '2.0');
        $channel = $rss->addChild('channel');
        $channel->addChild('title', 'The Guardian RSS Feed');
        $channel->addChild('link', 'https://www.theguardian.com');

        foreach ($data['response']['results'] as $article) {
            $rssItem = $channel->addChild('item');
            $rssItem->addChild('title', htmlspecialchars($article['webTitle']));
            $rssItem->addChild('link', htmlspecialchars($article['webUrl']));
        }

        // Format the XML with line breaks and indentation
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($rss->asXML());

        return $dom->saveXML();
    }
}
