<?php

namespace Webkul\AiReview\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\AiReview\DataGrids\ProductAiReviewDataGrid;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Repositories\ProductReviewRepository;

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
        if (request()->ajax()) {
            return datagrid(ProductAiReviewDataGrid::class)->process();
        }

        return view('ai_review::admin.ai-review.index');
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
}