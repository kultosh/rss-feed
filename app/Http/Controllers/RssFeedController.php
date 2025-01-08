<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class RssFeedController extends Controller
{
    public function getRssFeed($section)
    {
        $response = Http::get(env('GUARDAIN_URL').'sections', [
            'q' => $section,
            'api-key' => 'test',
            'format' => 'json',
        ]);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();
        $rssFeed = $this->convertToRss($data);

        return response($rssFeed, 200)->header('Content-Type', 'application/rss+xml');
    }

    private function convertToRss($data)
    {
        $rss = new SimpleXMLElement('<rss/>');
        $rss->addAttribute('version', '2.0');
        $channel = $rss->addChild('channel');
        $channel->addChild('title', 'The Guardian RSS Feed');
        $channel->addChild('link', 'https://www.theguardian.com');

        foreach ($data['response']['results'] as $article) {
            Log::info('Response from GUARDAIN', ['article' => $article]);

            $rssItem = $channel->addChild('item');
            $rssItem->addChild('title', htmlspecialchars($article['webTitle']));
            $rssItem->addChild('link', htmlspecialchars($article['webUrl']));
        }

        return $rss->asXML();
    }

}
