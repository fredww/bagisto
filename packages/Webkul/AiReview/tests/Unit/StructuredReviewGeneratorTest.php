<?php

use Webkul\AiReview\Services\StructuredReviewGenerator;

test('prompt includes strict JSON instruction and count', function () {
    $product = (object) ['name' => 'Test Product', 'description' => str_repeat('D', 120)];
    $gen = new StructuredReviewGenerator();
    $prompt = $gen->buildPrompt($product, ['languages' => ['English'], 'gender' => 'male', 'age_range' => '25-40'], 3);
    expect($prompt)->toContain('Return ONLY valid JSON');
    expect($prompt)->toContain('Generate exactly 3 reviews');
});

test('parse and validate accepts valid items and enforces constraints', function () {
    $json = json_encode([
        'reviews' => [
            [
                'title' => 'Great item',
                'content' => str_repeat('A', 120),
                'nickname' => 'Alice123',
                'rating' => 4,
                'purchaseDate' => '2025-01-10',
            ],
        ],
    ]);
    $gen = new StructuredReviewGenerator();
    $items = $gen->parseAndValidate($json, 1);
    expect($items)->toHaveCount(1);
    expect($items[0]['rating'])->toBeGreaterThanOrEqual(3);
    expect($items[0]['rating'])->toBeLessThanOrEqual(5);
});

