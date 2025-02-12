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
     * 
     * @param string $section The section for which the RSS feed is to be fetched.
     * @return Returns the RSS feed in XML format, or an error response if fetching fails
     * @throws \Exception If an error occurs while fetching the RSS feed or if the service fails
     */
    public function getRssFeed($section)
    {
        try {
            $validator = $this->validateSectionName($section);
            if ($validator->fails()) {
                $errors = $validator->errors();
                return $this->errorResponse($errors->first('section-name'), 'invalid', 422);
            }
            $rssFeed = $this->rssFeedService->fetchRssFeed($section);
            return response($rssFeed, 200)
                ->header('Content-Type', 'application/rss+xml');

        } catch (\Exception $error) {
            return $this->errorResponse($error->getMessage());
        }
    }

    /**
     * Return an error response as XML format.
     * 
     * @param string $message, string $status, int $code
     * @return \Illuminate\Http\Response
     */
    private function errorResponse($message, $status='error', $code=500)
    {
        Log::error('An error occurred while fetching the RSS feed', [
            'message' => $message,
            'status' => $status,
            'code' => $code
        ]);
        
        $xml = new SimpleXMLElement('<response/>');
        $xml->addChild('code', $code);
        $xml->addChild('status', $status);
        $xml->addChild('message', $message);
        $xml->addChild('content', '');

        // Format the XML with line breaks and indentation
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        return response($dom->saveXML(), $code)
            ->header('Content-Type', 'application/rss+xml');
    }

    /**
     * Validate the section name.
     * Validates the given section name using the 'required' rule and a custom 'SectionName' rule.
     * 
     * @param string $section The section name to validate.
     * @return \Illuminate\Validation\Validator The validator instance.
     */
    public function validateSectionName($section)
    {
        return Validator::make(
            ['section-name' => $section],
            ['section-name' => ['required', new SectionName]]  // Validation rules
        );
    }

}
