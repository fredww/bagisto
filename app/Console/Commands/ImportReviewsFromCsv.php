<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Repositories\ProductReviewRepository;
use Webkul\Product\Repositories\ProductReviewAttachmentRepository;
use Webkul\Product\Models\ProductFlat;

class ImportReviewsFromCsv extends Command
{
    protected $signature = 'reviews:import {file : CSV file path} {--dry-run : Parse without importing}';

    protected $description = 'Import product reviews from a CSV file';

    public function __construct(
        protected ProductRepository $productRepository,
        protected ProductReviewRepository $productReviewRepository,
        protected ProductReviewAttachmentRepository $attachmentRepository
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $file = $this->argument('file');

        if (! file_exists($file)) {
            $this->error('File not found: '.$file);
            return 1;
        }

        $dryRun = (bool) $this->option('dry-run');
        $handle = fopen($file, 'r');
        if (! $handle) {
            $this->error('Unable to open file: '.$file);
            return 1;
        }

        $header = null;
        $rowNumber = 0;
        $imported = 0;
        $skipped = 0;
        $errors = 0;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $rowNumber++;

            if ($rowNumber === 1) {
                $header = $this->normalizeHeader($row);
                continue;
            }

            if ($this->isInstructionRow($row)) {
                continue;
            }

            try {
                $data = $this->mapRow($row, $header);

                if (! $data) {
                    $skipped++;
                    continue;
                }

                $product = null;
                if (! empty($data['product_id'])) {
                    $product = $this->productRepository->find($data['product_id']);
                }
                if (! $product && ! empty($data['product_sku'])) {
                    $product = $this->productRepository->where('sku', $data['product_sku'])->first();
                }
                if (! $product && ! empty($data['product_title'])) {
                    $flat = ProductFlat::where('name', $data['product_title'])->first();
                    if ($flat) {
                        $product = $this->productRepository->find($flat->product_id);
                    }
                }
                if (! $product) {
                    $this->warn('Row '.$rowNumber.' skipped: product not found');
                    $skipped++;
                    continue;
                }

                $reviewPayload = [
                    'name'       => $data['customer_name'] ?? $this->generateGuestName(),
                    'title'      => $data['title'] ?? $this->makeTitleFromComment($data['comment'] ?? ''),
                    'rating'     => $data['rating'] ?? 5,
                    'comment'    => $data['comment'] ?? '',
                    'status'     => 'approved',
                    'product_id' => $product->id,
                    'customer_id'=> null,
                ];

                if ($dryRun) {
                    $imported++;
                    $this->line('Dry-run: would import review for product '.$product->id.' ('.$product->sku.')');
                    continue;
                }

                Event::dispatch('customer.review.create.before', $product->id);
                $review = $this->productReviewRepository->create($reviewPayload);
                Event::dispatch('customer.review.create.after', $reviewPayload);

                if (! empty($data['created_at'])) {
                    $review->created_at = Carbon::parse($data['created_at']);
                    $review->save();
                }

                if (! empty($data['attachments'])) {
                    $this->storeAttachmentsFromUrls($data['attachments'], $review->id);
                }

                $imported++;
            } catch (\Throwable $e) {
                $errors++;
                $this->error('Row '.$rowNumber.' error: '.$e->getMessage());
            }
        }

        fclose($handle);

        $this->info('Done. Imported: '.$imported.', Skipped: '.$skipped.', Errors: '.$errors);

        return $errors > 0 ? 1 : 0;
    }

    protected function normalizeHeader(array $header): array
    {
        return array_map(function ($h) {
            $h = trim($h ?? '');
            $map = [
                '商品ID' => 'product_id',
                '商品spu' => 'product_sku',
                '评论用户' => 'customer_name',
                '评论星级' => 'rating',
                '评论标题' => 'title',
                '评论内容' => 'comment',
                '评论日期' => 'created_at',
                '国家' => 'country',
                '评论图片或视频' => 'attachments',
                '回复内容' => 'reply_comment',
                '回复日期' => 'reply_date',
                '商品图片' => 'product_image',
                '商品标题' => 'product_title',
                '置顶评论' => 'pinned',
            ];
            return $map[$h] ?? $h;
        }, $header);
    }

    protected function isInstructionRow(array $row): bool
    {
        $first = $row[0] ?? '';
        if (! is_string($first)) {
            return false;
        }
        return str_contains($first, '导入');
    }

    protected function mapRow(array $row, array $header): ?array
    {
        $mapped = [];
        foreach ($header as $i => $key) {
            $mapped[$key] = $row[$i] ?? null;
        }

        $productId = $this->toInt($mapped['product_id'] ?? null);
        $productSku = trim((string) ($mapped['product_sku'] ?? ''));
        if (! $productId && $productSku === '') {
            return null;
        }

        $rating = $this->toInt($mapped['rating'] ?? null);
        if ($rating < 1 || $rating > 5) {
            $rating = 5;
        }

        $attachments = [];
        if (! empty($mapped['attachments'])) {
            $parts = array_filter(array_map('trim', explode(',', (string) $mapped['attachments'])));
            $attachments = array_slice($parts, 0, 5);
        }

        return [
            'product_id'   => $productId ?: null,
            'product_sku'  => $productSku ?: null,
            'customer_name'=> $this->sanitizeName($mapped['customer_name'] ?? null),
            'rating'       => $rating,
            'title'        => $this->sanitizeText($mapped['title'] ?? null, 100),
            'comment'      => $this->sanitizeText($mapped['comment'] ?? null, 2000),
            'created_at'   => $mapped['created_at'] ?? null,
            'attachments'  => $attachments,
            'product_title'=> $this->sanitizeText($mapped['product_title'] ?? null, 255),
        ];
    }

    protected function storeAttachmentsFromUrls(array $urls, int $reviewId): void
    {
        foreach ($urls as $url) {
            try {
                $response = Http::timeout(30)->get($url);
                if (! $response->successful()) {
                    continue;
                }

                $contentType = $response->header('Content-Type') ?: '';
                $type = explode('/', $contentType)[0] ?? 'image';

                $filename = $this->makeFilenameFromUrl($url, $contentType);
                $path = 'review/'.$reviewId.'/'.$filename;

                Storage::disk('public')->put($path, $response->body());

                $this->attachmentRepository->create([
                    'path'      => $path,
                    'review_id' => $reviewId,
                    'type'      => $type ?: 'image',
                    'mime_type' => $contentType ?: null,
                ]);
            } catch (\Throwable $e) {
                continue;
            }
        }
    }

    protected function makeFilenameFromUrl(string $url, ?string $contentType): string
    {
        $basename = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_BASENAME);
        $basename = $basename ?: Str::random(16);
        if ($contentType && ! str_contains($basename, '.')) {
            $ext = match ($contentType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'video/mp4' => 'mp4',
                default => 'bin',
            };
            $basename .= '.'.$ext;
        }
        return $basename;
    }

    protected function sanitizeName(?string $name): ?string
    {
        $name = $name !== null ? trim($name) : null;
        if ($name === '') {
            return null;
        }
        return mb_substr($name, 0, 63);
    }

    protected function sanitizeText(?string $text, int $limit): ?string
    {
        $text = $text !== null ? trim($text) : null;
        if ($text === '') {
            return null;
        }
        return mb_substr($text, 0, $limit);
    }

    protected function toInt($value): int
    {
        if ($value === null) {
            return 0;
        }
        return (int) preg_replace('/[^0-9]/', '', (string) $value);
    }

    protected function generateGuestName(): string
    {
        return 'Customer_'.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    protected function makeTitleFromComment(?string $comment): string
    {
        $comment = $comment ?? '';
        $comment = trim($comment);
        if ($comment === '') {
            return 'Review';
        }
        return mb_substr($comment, 0, 60);
    }
}