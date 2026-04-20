<?php
// src/Service/TranslateService.php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class TranslateService
{
    private $httpClient;
    private $cache;

    // Common travel phrases to pre-translate
    private const TRAVEL_PHRASES = [
        'en' => [
            'Hello' => 'Bonjour',
            'Thank you' => 'Merci',
            'Where is the bathroom?' => 'Où sont les toilettes ?',
            'How much does this cost?' => 'Combien ça coûte ?',
            'I need help' => "J'ai besoin d'aide",
            'Water' => 'Eau',
            'Food' => 'Nourriture',
            'Hotel' => 'Hôtel',
            'Airport' => 'Aéroport',
            'Train station' => 'Gare'
        ]
    ];

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->cache = new FilesystemAdapter('translate', 604800); // Cache 1 week
    }

    /**
     * Translate a phrase (with smart fallback to pre-defined travel phrases)
     */
    public function translate(string $text, string $from = 'en', string $to = 'fr'): string
    {
        // 1. Check pre-defined travel phrases first (instant, offline-capable)
        if ($from === 'en' && $to === 'fr' && isset(self::TRAVEL_PHRASES['en'][$text])) {
            return self::TRAVEL_PHRASES['en'][$text];
        }

        // 2. Try LibreTranslate API (free, no key required)
        return $this->cache->get("trans_{$from}_{$to}_" . md5($text), function () use ($text, $from, $to) {
            try {
                $response = $this->httpClient->request('POST', 'https://libretranslate.de/translate', [
                    'json' => [
                        'q' => $text,
                        'source' => $from,
                        'target' => $to,
                        'format' => 'text'
                    ],
                    'timeout' => 3 // Fast timeout for demo
                ]);
                $data = $response->toArray();
                return $data['translatedText'] ?? $text;
            } catch (\Exception $e) {
                // 3. Ultimate fallback: return original + note
                return $text . ' 🔍';
            }
        });
    }

    /**
     * Get all pre-defined travel phrases for a target language
     */
    public function getTravelPhrases(string $targetLang = 'fr'): array
    {
        if ($targetLang === 'fr') {
            return self::TRAVEL_PHRASES['en'];
        }
        // Could add more languages here
        return [];
    }
}