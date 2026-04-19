<?php
// src/Service/SentimentService.php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SentimentService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    
    // French sentiment keywords
    private const POSITIVE_FR = [
        'excellent', 'super', 'génial', 'parfait', 'merveilleux', 'formidable',
        'heureux', 'content', 'ravi', 'satisfait', 'recommande', 'adore',
        'fantastique', 'incroyable', 'extraordinaire', 'brillant', 'top',
        'bien', 'bon', 'beau', 'joli', 'sympa', 'agréable', 'plaisir', 'aime',
        'merci', 'impressionnant', 'extra', 'cool', 'superbe', 'waouh', 'trop bien'
    ];

    private const NEGATIVE_FR = [
        'mauvais', 'terrible', 'horrible', 'affreux', 'décevant', 'nul',
        'triste', 'mécontent', 'insatisfait', 'déteste', 'regrette',
        'catastrophe', 'désastre', 'pitoyable', 'lamentable', 'mal',
        'pas bien', 'bof', 'moyen', 'déçu', 'fâché', 'énervé', 'haine',
        'pas top', 'pas terrible', 'nullement', 'nul à chier'
    ];

    // English sentiment keywords
    private const POSITIVE_EN = [
        'excellent', 'great', 'amazing', 'perfect', 'wonderful', 'fantastic',
        'happy', 'content', 'delighted', 'satisfied', 'recommend', 'love',
        'incredible', 'awesome', 'brilliant', 'top', 'good', 'nice', 'beautiful',
        'lovely', 'pleasant', 'enjoy', 'thanks', 'thank', 'impressive', 'superb',
        'cool', 'fantabulous', 'outstanding', 'exceptional', 'marvelous',
        'wow', 'yay', 'fabulous', 'stellar', 'first-rate', 'five-star'
    ];

    private const NEGATIVE_EN = [
        'bad', 'terrible', 'horrible', 'awful', 'disappointing', 'poor',
        'sad', 'unhappy', 'dissatisfied', 'hate', 'regret',
        'disaster', 'catastrophe', 'pathetic', 'lousy', 'wrong',
        'not good', 'meh', 'mediocre', 'disappointed', 'angry', 'annoyed',
        'worst', 'useless', 'garbage', 'trash', 'failure', 'atrocious',
        'dreadful', 'abysmal', 'subpar', 'unacceptable', 'ugh', 'nope'
    ];

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $_ENV['HF_API_KEY'] ?? '';
    }

    public function analyze(string $text): array
    {
        // Try Hugging Face API first if key exists
        if (!empty($this->apiKey)) {
            $result = $this->callHuggingFaceAPI($text);
            if ($result !== null) {
                // API worked - return AI result
                return $result;
            }
            // API failed - log and fallback to offline
            error_log('⚠️ Hugging Face API unavailable - using offline analysis');
        } else {
            // No API key configured - use offline from the start
            error_log('ℹ️ No HF_API_KEY configured - using offline analysis');
        }
        
        // Fallback to offline keyword analysis (bilingual)
        return $this->offlineAnalysis($text);
    }
    
    private function callHuggingFaceAPI(string $text): ?array
    {
        try {
            $response = $this->httpClient->request('POST', 'https://api-inference.huggingface.co/models/cardiffnlp/twitter-roberta-base-sentiment-latest', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'inputs' => $text,
                    'options' => ['wait_for_model' => true]
                ],
                'timeout' => 10, // Reduced timeout for better UX
            ]);
            
            $data = $response->toArray();
            
            if (isset($data[0]) && is_array($data[0])) {
                $scores = [];
                foreach ($data[0] as $item) {
                    if (isset($item['label']) && isset($item['score'])) {
                        $scores[$item['label']] = $item['score'];
                    }
                }
                
                arsort($scores);
                $bestLabel = key($scores);
                $bestScore = current($scores);
                $confidence = round($bestScore * 100, 1);
                
                $sentiment = match($bestLabel) {
                    'LABEL_2', 'POSITIVE' => 'POSITIVE',
                    'LABEL_0', 'NEGATIVE' => 'NEGATIVE',
                    default => 'NEUTRAL',
                };
                
                $emoji = match($sentiment) {
                    'POSITIVE' => '🟢',
                    'NEGATIVE' => '🔴',
                    default => '🟡',
                };
                
                $label = match($sentiment) {
                    'POSITIVE' => 'Positif',
                    'NEGATIVE' => 'Négatif',
                    default => 'Neutre',
                };
                
                $color = match($sentiment) {
                    'POSITIVE' => '#2E7D32',
                    'NEGATIVE' => '#C62828',
                    default => '#F9A825',
                };
                
                return [
                    'sentiment' => $sentiment,
                    'emoji' => $emoji,
                    'label' => $label . ' 🤖 AI',
                    'color' => $color,
                    'confidence' => $confidence,
                    'ai_used' => true,
                    'message' => "Analyse IA - Confiance: {$confidence}%",
                ];
            }
            
        } catch (\Exception $e) {
            error_log('❌ Hugging Face API Error: ' . $e->getMessage());
        }
        
        return null;
    }
    
    private function offlineAnalysis(string $text): array
    {
        $textLower = strtolower(strip_tags($text));
        $words = preg_split('/[\s\p{P}]+/u', $textLower, -1, PREG_SPLIT_NO_EMPTY);

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($words as $word) {
            // Check French keywords
            if (in_array($word, self::POSITIVE_FR) || in_array($word, self::POSITIVE_EN)) {
                $positiveCount++;
            } elseif (in_array($word, self::NEGATIVE_FR) || in_array($word, self::NEGATIVE_EN)) {
                $negativeCount++;
            }
        }

        $total = $positiveCount + $negativeCount;
        
        if ($total === 0) {
            return $this->getDefaultResult();
        }

        $positiveRatio = $positiveCount / $total;
        $negativeRatio = $negativeCount / $total;

        if ($positiveRatio > 0.6 || $positiveCount > $negativeCount + 1) {
            return $this->getResult('POSITIVE', $positiveRatio);
        } elseif ($negativeRatio > 0.6 || $negativeCount > $positiveCount + 1) {
            return $this->getResult('NEGATIVE', $negativeRatio);
        } else {
            return $this->getResult('NEUTRAL', max($positiveRatio, $negativeRatio));
        }
    }

    private function getResult(string $sentiment, float $confidence): array
    {
        $emoji = match($sentiment) {
            'POSITIVE' => '🟢',
            'NEGATIVE' => '🔴',
            default => '🟡',
        };

        $label = match($sentiment) {
            'POSITIVE' => 'Positif',
            'NEGATIVE' => 'Négatif',
            default => 'Neutre',
        };

        $color = match($sentiment) {
            'POSITIVE' => '#2E7D32',
            'NEGATIVE' => '#C62828',
            default => '#F9A825',
        };

        return [
            'sentiment' => $sentiment,
            'emoji' => $emoji,
            'label' => $label . ' 📝 Local',
            'color' => $color,
            'confidence' => round($confidence * 100, 1),
            'ai_used' => false,
            'message' => "Analyse locale - Confiance: " . round($confidence * 100, 1) . "%",
        ];
    }

    private function getDefaultResult(): array
    {
        return [
            'sentiment' => 'NEUTRAL',
            'emoji' => '🟡',
            'label' => 'Neutre',
            'color' => '#F9A825',
            'confidence' => 50,
            'ai_used' => false,
            'message' => 'Aucun mot-clé détecté',
        ];
    }
}