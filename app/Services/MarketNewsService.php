<?php

namespace App\Services;

use App\Models\TelegramModel;
use Illuminate\Support\Facades\Http;

class MarketNewsService
{
    private const FEEDS = [
        'CNBC Indonesia' => 'https://www.cnbcindonesia.com/market/rss',
    ];

    private const GOOD_KEYWORDS = [
        'menguat', 'naik', 'melonjak', 'lompat', 'terbang', 'rebound', 'untung',
        'cuan', 'dividen', 'rekor', 'positif', 'optimis', 'tumbuh', 'surplus',
        'damai', 'pesta', 'hijau', 'borong', 'rally', 'reli',
    ];

    private const BAD_KEYWORDS = [
        'melemah', 'turun', 'anjlok', 'merosot', 'jeblok', 'rugi', 'tekan',
        'negatif', 'koreksi', 'longsor', 'ambruk', 'defisit', 'krisis', 'resesi',
        'merah', 'jual', 'outflow', 'gagal',
    ];

    public function fetchHeadlines(int $limit = 8): array
    {
        $headlines = [];

        foreach (self::FEEDS as $source => $url)
        {
            $items = $this->parseFeed($url, $source);
            $headlines = array_merge($headlines, $items);
        }

        return array_slice($headlines, 0, $limit);
    }

    private function parseFeed(string $url, string $source): array
    {
        $response = Http::timeout(20)->get($url);

        if (!$response->successful())
        {
            return [];
        }

        $xml = @simplexml_load_string($response->body());

        if ($xml === false || !isset($xml->channel->item))
        {
            return [];
        }

        $items = [];

        foreach ($xml->channel->item as $item)
        {
            $title = trim((string) $item->title);

            if ($title === '')
            {
                continue;
            }

            $items[] = [
                'title'     => $title,
                'link'      => trim((string) $item->link),
                'source'    => $source,
                'sentiment' => $this->classify($title),
            ];
        }

        return $items;
    }

    private function classify(string $title): string
    {
        $lower = mb_strtolower($title);

        foreach (self::BAD_KEYWORDS as $word)
        {
            if (str_contains($lower, $word))
            {
                return 'bad';
            }
        }

        foreach (self::GOOD_KEYWORDS as $word)
        {
            if (str_contains($lower, $word))
            {
                return 'good';
            }
        }

        return 'neutral';
    }

    public function buildMessage(array $headlines): string
    {
        $good = array_filter($headlines, fn ($h) => $h['sentiment'] === 'good');
        $bad = array_filter($headlines, fn ($h) => $h['sentiment'] === 'bad');
        $neutral = array_filter($headlines, fn ($h) => $h['sentiment'] === 'neutral');

        $time = now('Asia/Jakarta')->format('d M Y H:i') . ' WIB';

        $message = "📰 <b>RANGKUMAN BERITA PASAR — BURSA SAHAM INDONESIA</b>\n";
        $message .= $time . "\n";
        $message .= "==========================================\n\n";

        $message .= $this->buildSection('✅ <b>GOOD NEWS</b>', $good);
        $message .= $this->buildSection('⚠️ <b>BAD NEWS</b>', $bad);
        $message .= $this->buildSection('➖ <b>NEUTRAL</b>', $neutral);

        $message .= "==========================================\n";
        $message .= "<i>Sumber: CNBC Indonesia. Bukan rekomendasi beli/jual — selalu lakukan analisis sendiri.</i>";

        return $message;
    }

    private function buildSection(string $title, array $headlines): string
    {
        if (empty($headlines))
        {
            return '';
        }

        $section = $title . "\n\n";
        $number = 1;

        foreach ($headlines as $headline)
        {
            $text = htmlspecialchars($headline['title'], ENT_QUOTES, 'UTF-8');
            $link = htmlspecialchars($headline['link'], ENT_QUOTES, 'UTF-8');

            $section .= "{$number}. <a href=\"{$link}\">{$text}</a>\n";
            $number++;
        }

        return $section . "\n";
    }

    public function dispatch(): array
    {
        $headlines = $this->fetchHeadlines();

        if (empty($headlines))
        {
            return ['ok' => false, 'reason' => 'no_headlines'];
        }

        $message = $this->buildMessage($headlines);

        $token = config('services.telegram.bot_token');
        $chatID = config('services.telegram.chat_id');
        $threadID = config('services.telegram.thread_id');

        TelegramModel::sendMessageThread($token, $chatID, $threadID, $message);

        return ['ok' => true, 'count' => count($headlines)];
    }
}
