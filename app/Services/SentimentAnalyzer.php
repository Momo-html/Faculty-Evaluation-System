<?php

namespace App\Services;

class SentimentAnalyzer
{
    /** @var list<string> */
    private array $positiveWords = [
        'clear',
        'helpful',
        'excellent',
        'good',
        'great',
        'organized',
        'approachable',
        'understand',
        'engaging',
        'kind',
        'fair',
    ];

    /** @var list<string> */
    private array $negativeWords = [
        'unclear',
        'late',
        'confusing',
        'difficult',
        'bad',
        'poor',
        'rude',
        'unfair',
        'boring',
        'hard',
        'absent',
    ];

    public function classify(?string $text): string
    {
        $text = strtolower((string) $text);

        if (trim($text) === '') {
            return 'Neutral';
        }

        $positiveScore = $this->score($text, $this->positiveWords);
        $negativeScore = $this->score($text, $this->negativeWords);

        return match (true) {
            $positiveScore > $negativeScore => 'Positive',
            $negativeScore > $positiveScore => 'Negative',
            default => 'Neutral',
        };
    }

    /**
     * @param  list<string>  $words
     */
    private function score(string $text, array $words): int
    {
        return collect($words)->sum(fn (string $word): int => substr_count($text, $word));
    }
}
