<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Rule-based text moderation via Sightengine (profanity / insults / …).
 *
 * @see https://sightengine.com/docs/text-moderation-guide
 */
class SightengineModerationService
{
    private const CHECK_URL = 'https://api.sightengine.com/1.0/text/check.json';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $apiUser = null,
        private readonly ?string $apiSecret = null,
    ) {
    }

    /**
     * Returns true if the text should be blocked (profanity rules matched).
     * If credentials are missing or the API errors, returns false (do not block submit).
     */
    public function textHasIssues(string $text): bool
    {
        $text = trim($text);
        if ('' === $text) {
            return false;
        }

        if (null === $this->apiUser || '' === $this->apiUser
            || null === $this->apiSecret || '' === $this->apiSecret) {
            return false;
        }

        foreach (['fr', 'en'] as $lang) {
            if ($this->checkLanguage($text, $lang)) {
                return true;
            }
        }

        return false;
    }

    private function checkLanguage(string $text, string $lang): bool
    {
        try {
            $response = $this->httpClient->request('POST', self::CHECK_URL, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'body' => [
                    'text' => $text,
                    'lang' => $lang,
                    'mode' => 'rules',
                    'opt_countries' => 'fr,us,gb',
                    'api_user' => $this->apiUser,
                    'api_secret' => $this->apiSecret,
                ],
                'timeout' => 20,
            ]);

            /** @var array<string, mixed> $data */
            $data = $response->toArray(false);

            if (($data['status'] ?? '') !== 'success') {
                return false;
            }

            $matches = $data['profanity']['matches'] ?? null;

            return \is_array($matches) && [] !== $matches;
        } catch (ExceptionInterface) {
            return false;
        }
    }
}
