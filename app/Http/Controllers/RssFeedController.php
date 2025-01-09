<?php

namespace App\Http\Controllers;

use App\Rules\SectionName;
use App\Services\RssFeedService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use SimpleXMLElement;

class RssFeedController extends Controller
{
    protected $rssFeedService;

    public function __construct(RssFeedService $rssFeedService)
    {
        $this->rssFeedService = $rssFeedService;
    }

    /**
     * Fetches the RSS feed for a given section.
     * @param string $section The section for which the RSS feed is to be fetched.
     * @return Returns the RSS feed in XML format, or an error response if fetching fails
     * @throws \Exception If an error occurs while fetching the RSS feed or if the service fails
     */
    public function getRssFeed($section)
    {
        $validator = $this->validateSectionName($section);
        if ($validator->fails()) {
            return $this->errorResponse('Invalid section name!');
        }

        try {
            $rssFeed = $this->rssFeedService->fetchRssFeed($section);
            return response($rssFeed, 200)
                ->header('Content-Type', 'application/rss+xml');

        } catch (\Exception $error) {
            // Log the exception using Monolog
            Log::error('An error occurred while fetching the RSS feed', [
                'error_message' => $error->getMessage(),
                'error_line' => $error->getLine(),
                'stack_trace' => $error->getTraceAsString()
            ]);
            
            return $this->errorResponse($error->getMessage());
        }
    }

    /**
     * Return an error response as XML format.
     * @param string $message
     * @return \Illuminate\Http\Response
     */
    private function errorResponse($message)
    {
        $xml = new SimpleXMLElement('<response/>');
        $xml->addChild('code', 500);
        $xml->addChild('status', 'error');
        $xml->addChild('message', $message);
        $xml->addChild('content', '');

        // Format the XML with line breaks and indentation
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        return response($dom->saveXML(), 500)
            ->header('Content-Type', 'application/rss+xml');
    }

    /**
     * Validate the section name.
     * Validates the given section name using the 'required' rule and a custom 'SectionName' rule.
     * @param string $section The section name to validate.
     * @return \Illuminate\Validation\Validator The validator instance.
     */
    public function validateSectionName($section)
    {
        return Validator::make(
            ['section' => $section],
            ['section' => ['required', new SectionName]]  // Validation rules
        );
    }

}
