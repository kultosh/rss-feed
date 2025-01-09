<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use SimpleXMLElement;
use Exception;
use Illuminate\Support\Facades\Cache;
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
        $cacheKey = "rss_feed_{$section}";
        $sectionCacheTime = (int) env('GUARDIAN_CACHE_TIME', 10);
        // Cache the specific section till defined time
        $rssFeed = Cache::remember($cacheKey, now()->addMinutes($sectionCacheTime), function () use ($section) {
            return $this->getRssFeedFromApi($section);
        });

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
     * @return string Returns the RSS feed as XML if the API request is successful or Returns the warning if the request fails.
     */
    private function getRssFeedFromApi(string $section)
    {
        $response = Http::get(env('GUARDIAN_URL').'search', [
            'section' => $section,
            'api-key' => env('GUARDIAN_API_KEY'),
            'format' => 'json',
            'show-fields' => 'all',
            'page-size' => 10,
            'order-by' => 'newest'
        ]);

        if ($response->failed() || empty($response->json()['response']['results'])) {
            Log::warning('No articles found for section', ['section' => $section]);
            return $this->generateEmptyRss($section);
        }

        $data = $response->json();
        Log::info('Response from GUARDIAN', ['data' => $data]);
        return $this->convertToRss($data, $section);
    }

    /**
     * Converts the raw API response data into an RSS 2.0 feed format.
     * This method takes the JSON response from the Guardian API and converts it into a valid RSS 2.0 XML feed.
     * Each article in the API response is transformed into an RSS `<item>` with relevant details such as title,
     * link, description, publication date, and category. It also includes an atom:link for self-referencing the RSS feed.
     * Optionally, if a thumbnail is available for the article, it will be added as a media:thumbnail.
     * 
     * @param array $data The raw data from the Guardian API response in JSON format.
     * @param string $section The name of the section (e.g., "sport", "news") that the articles belong to.
     * @return string The generated RSS feed in XML format.
     */
    private function convertToRss(array $data, $section)
    {
        $rss = new SimpleXMLElement('<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/"></rss>');
        $channel = $rss->addChild('channel');
        $channel->addChild('title', 'Guardian RSS Feed');
        $channel->addChild('link', 'https://www.theguardian.com');
        $channel->addChild('description', 'Latest '.$section.' from The Guardian');
        
        $atomLink = $channel->addChild('atom:link', null, 'http://www.w3.org/2005/Atom');
        $atomLink->addAttribute('href', url()->current());
        $atomLink->addAttribute('rel', 'self');

        foreach ($data['response']['results'] as $article) {
            $rssItem = $channel->addChild('item');
            $rssItem->addChild('title', htmlspecialchars($article['webTitle']));
            $rssItem->addChild('link', htmlspecialchars($article['webUrl']));
            $rssItem->addChild('description', htmlspecialchars($article['fields']['trailText'] ?? 'No description available'));
            $rssItem->addChild('category', htmlspecialchars($article['sectionName']));
            $rssItem->addChild('pubDate', date(DATE_RSS, strtotime($article['webPublicationDate'])));
            $rssItem->addChild('guid', htmlspecialchars($article['webUrl']));
            if (!empty($article['fields']['thumbnail'])) {
                $mediaThumbnail = $rssItem->addChild('media:thumbnail', null, 'http://search.yahoo.com/mrss/');
                $mediaThumbnail->addAttribute('url', htmlspecialchars($article['fields']['thumbnail']));
            }
        }

        return $this->formatXML($rss);
    }
    
    /**
     * Generates an empty RSS feed with a message indicating no articles were found for a specific section.
     * The RSS feed includes an atom:link element to a search page for the section.
     * 
     * @param string $section The section for which the RSS feed is generated (e.g., "sport", "news").
     * @return string The formatted XML of the empty RSS feed.
     */
    private function generateEmptyRss(string $section)
    {
        $rss = new SimpleXMLElement('<rss/>');
        $rss->addAttribute('version', '2.0');
        $channel = $rss->addChild('channel');
        $channel->addChild('title', 'No Articles Found');
        $channel->addChild('link', 'https://www.theguardian.com');
        $channel->addChild('description', "No articles available for the section: {$section}");
        
        return $this->formatXML($rss);
    }

    /**
     * Formats the given SimpleXMLElement to a well-structured XML string with indentation and line breaks.
     * This function takes the provided RSS XML data, uses PHP's DOMDocument class to format it with
     * proper indentation and line breaks for easier readability. It returns the formatted XML as a string.
     * 
     * @param SimpleXMLElement $rss The RSS feed to be formatted.
     * @return string The formatted XML string.
     */
    private function formatXML($rss)
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($rss->asXML());

        return $dom->saveXML();
    }

}
