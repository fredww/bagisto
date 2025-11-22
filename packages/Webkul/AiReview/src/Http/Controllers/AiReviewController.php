<?php

namespace Webkul\AiReview\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\AiReview\DataGrids\ProductAiReviewDataGrid;
use Webkul\AiReview\Models\AiModel;
use Webkul\AiReview\Models\AiReviewJob;
use Webkul\AiReview\Models\AiReviewLog;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Repositories\ProductReviewRepository;
use Webkul\MagicAI\MagicAI;

class AiReviewController extends Controller
{
    /**
     * 中文：构造函数，注入商品与评论仓库。
     * Purpose: Inject product and product review repositories.
     */
    public function __construct(
        protected ProductRepository $productRepository,
        protected ProductReviewRepository $productReviewRepository
    ) {}

    /**
     * 中文：AI 评测管理页面，支持 AJAX 加载数据表格。
     * Purpose: Render AI Review admin page; return datagrid JSON for AJAX.
     */
    public function index()
    {
        if (
            request()->ajax()
            || request()->has('pagination')
            || request()->has('filters')
            || request()->has('sort')
            || request()->has('export')
        ) {
            return datagrid(ProductAiReviewDataGrid::class)->process();
        }

        $models = AiModel::where('enabled', true)->orderBy('name')->get();

        return view('ai_review::admin.ai-review.index', compact('models'));
    }

    /**
     * 中文：为单个商品生成指定数量的 AI 评测。
     * Purpose: Generate the given number of AI reviews for one product.
     */
    public function generate(Request $request): JsonResponse
    {
        $this->validate($request, [
            'product_id' => 'required|integer',
            'count'      => 'required|integer|min:1|max:5',
        ]);

        $productId = (int) $request->input('product_id');
        $count     = (int) $request->input('count');

        // Ensure product exists
        $product = $this->productRepository->findOrFail($productId);

        $created = $this->createReviewsForProduct($product->id, $count);

        return new JsonResponse([
            'message' => trans('admin::app.response.create-success', ['name' => 'AI Reviews']),
            'created' => $created,
        ], 200);
    }

    /**
     * 中文：批量为多个商品生成 AI 评测。
     * Purpose: Mass-generate reviews for selected products from the datagrid.
     */
    public function massGenerate(Request $request): JsonResponse
    {
        $this->validate($request, [
            'indices' => 'required|array|min:1',
            'value'   => 'required|integer|min:1|max:5',
        ]);

        $productIds = array_map('intval', $request->input('indices'));
        $count      = (int) $request->input('value');

        $totalCreated = 0;

        foreach ($productIds as $productId) {
            try {
                $product = $this->productRepository->findOrFail($productId);
                $totalCreated += $this->createReviewsForProduct($product->id, $count);
            } catch (\Exception $e) {
                // Skip invalid product IDs; continue processing
                continue;
            }
        }

        return new JsonResponse([
            'message' => trans('admin::app.response.create-success', ['name' => 'AI Reviews']),
            'created' => $totalCreated,
        ], 200);
    }

    /**
     * 中文：根据商品生成评测标题与内容（简版，无外部依赖）。
     * Purpose: Build a simple AI-like review title and comment for the product.
     */
    protected function makeReviewContent(string $productName): array
    {
        // Keep logic predictable and quick; no external calls
        $adjectives = ['Fantastic', 'Great', 'Excellent', 'Impressive', 'Solid'];
        $title = $adjectives[array_rand($adjectives)] . ' ' . $productName;

        $comment = sprintf(
            'This %s exceeded expectations in quality and value. Highly recommended!',
            $productName
        );

        // Favor positive ratings by default
        $rating = rand(4, 5);

        return [
            'title'   => $title,
            'comment' => $comment,
            'rating'  => $rating,
        ];
    }

    /**
     * 中文：为指定商品创建多条评测记录。
     * Purpose: Create N reviews for a product using the repository.
     */
    protected function createReviewsForProduct(int $productId, int $count): int
    {
        $created = 0;

        for ($i = 0; $i < $count; $i++) {
            $product = $this->productRepository->findOrFail($productId);

            $content = $this->makeReviewContent($product->name);

            $data = [
                'name'       => 'AI Review Bot',
                'title'      => $content['title'],
                'rating'     => $content['rating'],
                'comment'    => $content['comment'],
                'status'     => 'approved',
                'product_id' => $productId,
                'customer_id'=> null,
            ];

            try {
                Event::dispatch('customer.review.create.before', $productId);

                $this->productReviewRepository->create($data);

                Event::dispatch('customer.review.create.after', $data);

                $created++;
            } catch (\Exception $e) {
                // Skip on error and continue
                continue;
            }
        }

        return $created;
    }

    public function generateBatch(Request $request): JsonResponse
    {
        $this->validate($request, [
            'product_ids'   => 'required|array|min:1',
            'min_count'     => 'required|integer|min:1',
            'max_count'     => 'required|integer|min:1|gte:min_count',
            'gender'        => 'nullable|string|in:male,female,any',
            'age_range'     => 'nullable|string|max:50',
            'regions'       => 'nullable|array',
            'languages'     => 'nullable|array',
            'date_range'    => 'nullable|array',
            'model_id'      => 'nullable|integer|exists:ai_models,id',
            'instruction'   => 'nullable|string|max:2000',
        ]);

        $job = AiReviewJob::create([
            'ai_model_id'      => $request->input('model_id'),
            'total_products'   => count($request->input('product_ids')),
            'processed_products'=> 0,
            'status'           => 'processing',
            'config'           => [
                'min'        => (int) $request->input('min_count'),
                'max'        => (int) $request->input('max_count'),
                'gender'     => $request->input('gender'),
                'age_range'  => $request->input('age_range'),
                'regions'    => $request->input('regions') ?? [],
                'languages'  => $request->input('languages') ?? [],
                'date_range' => $request->input('date_range') ?? [],
                'instruction'=> $request->input('instruction'),
            ],
        ]);

        $model = $job->ai_model_id ? AiModel::find($job->ai_model_id) : null;

        $createdTotal = 0;
        foreach ($request->input('product_ids') as $pid) {
            $start = microtime(true);
            try {
                $product = $this->productRepository->findOrFail((int) $pid);
                $count = random_int($job->config['min'], $job->config['max']);

                $reviewIds = [];

                if ($model) {
                    $generator = new \Webkul\AiReview\Services\StructuredReviewGenerator();
                    $prompt = $generator->buildPrompt($product, $job->config, $count);
                    $temperature = 0.7;
                    try {
                        $response = $generator->callModel($model, $prompt, $temperature, 30, 2);
                        $items = $generator->parseAndValidate($response, $count);
                    } catch (\Throwable $e) {
                        $items = [];
                    }

                    if (!empty($items)) {
                        foreach ($items as $it) {
                            Event::dispatch('customer.review.create.before', $product->id);
                            $review = $this->productReviewRepository->create([
                                'name'        => $it['nickname'],
                                'title'       => $it['title'],
                                'rating'      => $it['rating'],
                                'comment'     => $it['content'] . ' (Purchase Date: ' . $it['purchaseDate'] . ')',
                                'status'      => core()->getConfigData('catalog.products.ai_review.default_status') ?? 'approved',
                                'product_id'  => $product->id,
                                'customer_id' => null,
                            ]);
                            Event::dispatch('customer.review.create.after', $review);
                            $reviewIds[] = $review->id;
                            $createdTotal++;
                        }
                    } else {
                        for ($i = 0; $i < $count; $i++) {
                            $content = $this->generateSmartContent($product, $job, $model);
                            Event::dispatch('customer.review.create.before', $product->id);
                            $review = $this->productReviewRepository->create([
                                'name'        => 'AI Review Bot',
                                'title'       => $content['title'],
                                'rating'      => $content['rating'],
                                'comment'     => $content['comment'],
                                'status'      => core()->getConfigData('catalog.products.ai_review.default_status') ?? 'approved',
                                'product_id'  => $product->id,
                                'customer_id' => null,
                            ]);
                            Event::dispatch('customer.review.create.after', $review);
                            $reviewIds[] = $review->id;
                            $createdTotal++;
                        }
                    }
                } else {
                    for ($i = 0; $i < $count; $i++) {
                        $content = $this->makeReviewContent($product->name);
                        Event::dispatch('customer.review.create.before', $product->id);
                        $review = $this->productReviewRepository->create([
                            'name'        => 'AI Review Bot',
                            'title'       => $content['title'],
                            'rating'      => $content['rating'],
                            'comment'     => $content['comment'],
                            'status'      => core()->getConfigData('catalog.products.ai_review.default_status') ?? 'approved',
                            'product_id'  => $product->id,
                            'customer_id' => null,
                        ]);
                        Event::dispatch('customer.review.create.after', $review);
                        $reviewIds[] = $review->id;
                        $createdTotal++;
                    }
                }

                $duration = (int) ((microtime(true) - $start) * 1000);
                $message = json_encode([
                    'model_id' => $model?->model_id,
                    'provider' => $model?->provider,
                    'temperature' => 0.7,
                    'count' => count($reviewIds),
                    'duration_ms' => $duration,
                ]);

                AiReviewLog::create([
                    'job_id'        => $job->id,
                    'product_id'    => $product->id,
                    'status'        => 'created',
                    'message'       => $message,
                    'created_count' => count($reviewIds),
                ]);

                $job->processed_products++;
                $job->save();
            } catch (\Throwable $e) {
                AiReviewLog::create([
                    'job_id'     => $job->id,
                    'product_id' => (int) $pid,
                    'status'     => 'error',
                    'message'    => $e->getMessage(),
                ]);
            }
        }

        $job->status = 'completed';
        $job->save();

        return new JsonResponse(['job_id' => $job->id, 'created' => $createdTotal], 200);
    }

    protected function generateSmartContent($product, AiReviewJob $job, ?AiModel $model): array
    {
        $ratingMin = (int) core()->getConfigData('catalog.products.ai_review.min_rating') ?: 4;
        $ratingMax = (int) core()->getConfigData('catalog.products.ai_review.max_rating') ?: 5;

        $title = $product->name;
        $prompt = $this->buildPrompt($product, $job->config);

        $comment = $prompt;

        if ($model) {
            if ($model->provider === 'openai' && is_array($model->auth)) {
                if (!empty($model->auth['api_key'])) {
                    config(['openai.api_key' => $model->auth['api_key']]);
                }
                if (!empty($model->auth['organization'])) {
                    config(['openai.organization' => $model->auth['organization']]);
                }
            }

            $ai = (new MagicAI())
                ->setModel($model->model_id)
                ->setPrompt($prompt)
                ->setTemperature(0.7);

            try {
                $comment = $ai->ask();
                $title = mb_strimwidth($comment, 0, 60, '...');
            } catch (\Throwable $e) {
                $comment = $prompt;
            }
        }

        $comment = $this->applyCompliance($comment, $job->config['regions'] ?? [], $job->config['languages'] ?? []);

        return [
            'title'   => $title,
            'comment' => $comment,
            'rating'  => random_int($ratingMin, $ratingMax),
        ];
    }

    protected function buildPrompt($product, array $config): string
    {
        $parts = [];
        $parts[] = 'Write product review';
        if (!empty($config['languages'])) {
            $parts[] = 'in '.implode(',', $config['languages']);
        }
        if (!empty($config['gender']) && $config['gender'] !== 'any') {
            $parts[] = 'for '.$config['gender'];
        }
        if (!empty($config['age_range'])) {
            $parts[] = 'age '.$config['age_range'];
        }
        if (!empty($config['regions'])) {
            $parts[] = 'target regions '.implode(',', $config['regions']);
        }
        if (!empty($config['instruction'])) {
            $parts[] = $config['instruction'];
        }
        $parts[] = 'Product: '.$product->name;
        $parts[] = 'SKU: '.$product->sku;
        $parts[] = 'Description: '.($product->description ?? '');
        return implode('. ', array_filter($parts));
    }

    protected function applyCompliance(string $text, array $regions, array $languages): string
    {
        $blacklist = ['illegal', 'banned'];
        foreach ($blacklist as $word) {
            $text = str_ireplace($word, 'restricted', $text);
        }
        return $text;
    }

    public function jobStatus(int $id): JsonResponse
    {
        $job = AiReviewJob::findOrFail($id);
        $logs = AiReviewLog::where('job_id', $id)->orderBy('id')->get();
        return new JsonResponse([
            'status' => $job->status,
            'processed' => $job->processed_products,
            'total' => $job->total_products,
            'logs' => $logs,
        ]);
    }

    public function exportJob(int $id)
    {
        $logs = AiReviewLog::where('job_id', $id)->get();
        $rows = [];
        foreach ($logs as $log) {
            $rows[] = [
                'product_id' => $log->product_id,
                'status'     => $log->status,
                'message'    => $log->message,
                'created'    => $log->created_count,
            ];
        }
        $csv = implode(",\n", array_map(function ($r) {
            return implode(',', $r);
        }, $rows));
        $path = 'ai-review/job-'.$id.'-summary.csv';
        Storage::disk('local')->put($path, $csv);
        return response()->download(storage_path('app/'.$path))->deleteFileAfterSend(true);
    }
}
