<?php

namespace Webkul\AiReview\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Webkul\AiReview\Models\AiModel;
use Webkul\MagicAI\MagicAI;

class StructuredReviewGenerator
{
    public function buildPrompt($product, array $config, int $count): string
    {
        $name = $product->name ?? '';
        $categories = method_exists($product, 'categories') ? $product->categories->pluck('name')->filter()->values()->all() : [];
        $features = [];
        $age = Arr::get($config, 'age_range');
        $gender = Arr::get($config, 'gender');
        $regions = Arr::get($config, 'regions', []);
        $languages = Arr::get($config, 'languages', []);
        $dateRange = Arr::get($config, 'date_range', []);
        $instruction = Arr::get($config, 'instruction');

        $requirement = [
            'reviews' => [
                'title' => '<=20 chars',
                'content' => '100-200 chars',
                'nickname' => 'random english',
                'rating' => 'integer 3-5',
                'purchaseDate' => 'YYYY-MM-DD',
            ],
        ];

        $schema = json_encode([
            'reviews' => [
                [
                    'title' => 'string',
                    'content' => 'string',
                    'nickname' => 'string',
                    'rating' => 'number',
                    'purchaseDate' => 'string',
                ],
            ],
        ], JSON_UNESCAPED_UNICODE);

        $parts = [];
        $parts[] = 'You are a helpful assistant that writes product reviews.';
        if ($languages) {
            $parts[] = 'Write in languages: ' . implode(',', $languages) . '.';
        }
        if ($gender && $gender !== 'any') {
            $parts[] = 'Target gender: ' . $gender . '.';
        }
        if ($age) {
            $parts[] = 'Target age range: ' . $age . '.';
        }
        if ($regions) {
            $parts[] = 'Target regions: ' . implode(',', $regions) . '.';
        }
        if ($instruction) {
            $parts[] = 'Additional instruction: ' . $instruction . '.';
        }
        $parts[] = 'Product name: ' . $name . '.';
        if ($categories) {
            $parts[] = 'Categories: ' . implode(',', $categories) . '.';
        }
        if (!empty($product->description)) {
            $parts[] = 'Description: ' . Str::limit(strip_tags($product->description), 400);
        }

        $parts[] = 'Generate exactly ' . $count . ' reviews following constraints:';
        $parts[] = '• title ≤ 20 characters; • content between 100 and 200 characters; • nickname is random English; • rating integer 3 to 5; • purchaseDate within requested date range if provided or a plausible recent date.';
        $parts[] = 'Return ONLY valid JSON with this shape and no extra text: {"reviews": [{"title":"..","content":"..","nickname":"..","rating":4,"purchaseDate":"2025-01-10"}, ...]}.';
        $parts[] = 'Do not include markdown, explanations, or backticks.';

        return implode("\n", $parts);
    }

    public function callModel(AiModel $model, string $prompt, float $temperature = 0.7, int $timeoutSeconds = 30, int $retries = 2): string
    {
        $lastException = null;
        for ($i = 0; $i <= $retries; $i++) {
            try {
                if ($model->provider === 'openai' && $model->api_endpoint) {
                    $endpoint = rtrim($model->api_endpoint, '/') . '/chat/completions';
                    $resp = Http::timeout($timeoutSeconds)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . ($model->auth['api_key'] ?? ''),
                            'Content-Type' => 'application/json',
                        ])->post($endpoint, [
                            'model' => $model->model_id,
                            'temperature' => $temperature,
                            'response_format' => ['type' => 'json_object'],
                            'messages' => [
                                ['role' => 'system', 'content' => 'Return strict JSON only.'],
                                ['role' => 'user', 'content' => $prompt],
                            ],
                        ]);
                    if ($resp->successful()) {
                        $choices = $resp->json('choices');
                        $content = Arr::get($choices, '0.message.content');
                        if (is_string($content) && strlen($content)) {
                            return $content;
                        }
                        throw new \RuntimeException('Empty content');
                    }
                    throw new \RuntimeException('API error: ' . $resp->status());
                }

                $ai = (new MagicAI())
                    ->setModel($model->model_id)
                    ->setPrompt($prompt)
                    ->setTemperature($temperature);
                $content = $ai->ask();
                if (is_string($content) && strlen($content)) {
                    return $content;
                }
                throw new \RuntimeException('Empty content');
            } catch (\Throwable $e) {
                $lastException = $e;
                if ($i < $retries) {
                    usleep(400000);
                    continue;
                }
                throw $e;
            }
        }
        throw $lastException ?? new \RuntimeException('Unknown error');
    }

    public function parseAndValidate(string $json, int $expectedCount): array
    {
        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['reviews']) || !is_array($data['reviews'])) {
            throw new \InvalidArgumentException('Invalid JSON structure');
        }
        $items = $data['reviews'];
        if (count($items) < 1) {
            throw new \InvalidArgumentException('No reviews returned');
        }
        $validated = [];
        foreach ($items as $item) {
            $title = trim((string) Arr::get($item, 'title'));
            $content = trim((string) Arr::get($item, 'content'));
            $nickname = trim((string) Arr::get($item, 'nickname'));
            $rating = (int) Arr::get($item, 'rating');
            $purchaseDate = trim((string) Arr::get($item, 'purchaseDate'));

            if ($title === '' || mb_strlen($title) > 20) {
                continue;
            }
            $len = mb_strlen($content);
            if ($len < 100 || $len > 200) {
                continue;
            }
            if ($rating < 3 || $rating > 5) {
                continue;
            }
            if ($nickname === '' || !preg_match('/^[A-Za-z][A-Za-z0-9_\-]{2,30}$/', $nickname)) {
                continue;
            }
            if ($purchaseDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $purchaseDate)) {
                continue;
            }
            $validated[] = compact('title', 'content', 'nickname', 'rating', 'purchaseDate');
            if (count($validated) >= $expectedCount) {
                break;
            }
        }
        if (count($validated) === 0) {
            throw new \InvalidArgumentException('No valid reviews after validation');
        }
        return $validated;
    }
}

