<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Webkul\Product\Models\ProductFlat;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Helpers\Indexers\Price as PriceIndexer;
use Webkul\Product\Repositories\ProductCustomerGroupPriceRepository;
use Webkul\CatalogRule\Repositories\CatalogRuleProductPriceRepository;
use Spatie\ResponseCache\Facades\ResponseCache;

class RandomizeProductPriceByName extends Command
{
    //php artisan product:randomize-price-by-name "Big Effing Clip in Toasted Sugar" --min=20 --max=23 --dry-run
    //sudo -u www php artisan responsecache:clear
    protected $signature = 'product:randomize-price-by-name 
                            {name : Product name to match}
                            {--min= : Minimum integer price}
                            {--max= : Maximum integer price}
                            {--dry-run : Preview without saving}
                            {--keep-special : Do not clear special price}
                            {--skip-relations : Do not update variants/parent}
                            {--clear-group-prices : Remove customer group prices for matched products}
                            {--clear-catalog-rules : Remove catalog rule prices for matched products}
                            {--clear-response-cache : Clear storefront response cache}
                            {--show-indices : Print price indices after update}';

    protected $description = '批量将指定名称的产品价格随机设置为给定区间的整数';

    public function __construct(
        protected ProductRepository $productRepository,
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $name = trim((string) $this->argument('name'));
        $min = (int) ($this->option('min') ?? 0);
        $max = (int) ($this->option('max') ?? 0);
        $dryRun = (bool) $this->option('dry-run');
        $keepSpecial = (bool) $this->option('keep-special');
        $skipRelations = (bool) $this->option('skip-relations');
        $clearGroupPrices = (bool) $this->option('clear-group-prices');
        $clearCatalogRules = (bool) $this->option('clear-catalog-rules');

        if ($name === '' || $min <= 0 || $max <= 0 || $min > $max) {
            $this->error('参数错误：请提供有效的 name、--min 和 --max，且 --min <= --max');
            return self::FAILURE;
        }

        $productIds = ProductFlat::query()
            ->where('name', $name)
            ->distinct()
            ->pluck('product_id')
            ->all();

        $count = count($productIds);
        if ($count === 0) {
            $this->info('未找到匹配名称的产品：'.$name);
            return self::SUCCESS;
        }

        $this->info('匹配到产品数：'.$count.'（按名称精确匹配）');

        if ($dryRun) {
            $preview = array_slice($productIds, 0, 20);
            foreach ($preview as $pid) {
                $price = random_int($min, $max);
                $this->line("产品ID #{$pid} 将更新为价格 {$price}");
            }
            if ($count > 20) {
                $this->line('... 其余省略');
            }
            $this->line('Dry-run 模式：未应用任何更改');
            return self::SUCCESS;
        }
        
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $updated = 0;

        foreach ($productIds as $productId) {
            $price = (float) random_int($min, $max);
            $updated += $this->applyPriceCascade($productId, $price, $keepSpecial, $skipRelations);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('已更新记录数：'.$updated);

        if ($clearGroupPrices || $clearCatalogRules) {
            $this->newLine();
        }

        if ($clearGroupPrices) {
            $this->clearCustomerGroupPrices($productIds);
        }

        if ($clearCatalogRules) {
            $this->clearCatalogRulePrices($productIds);
        }

        $this->reindexPrices($productIds);

        if ((bool) $this->option('clear-response-cache')) {
            ResponseCache::clear();
            $this->info('前台响应缓存已清理');
        }

        if ((bool) $this->option('show-indices')) {
            $this->printPriceIndices($productIds);
        }

        return self::SUCCESS;
    }

    protected function applyPrice(int $productId, float $price, bool $keepSpecial = false): void
    {
        $payload = ['price' => $price];
        $attributes = ['price'];
        if (! $keepSpecial) {
            $payload['special_price'] = null;
            $payload['special_price_from'] = null;
            $payload['special_price_to'] = null;
            $attributes = ['price', 'special_price', 'special_price_from', 'special_price_to'];
        }

        $this->productRepository->update($payload, $productId, $attributes);

        $product = $this->productRepository->find($productId);
        if ($product) {
            Event::dispatch('catalog.product.update.after', $product);
        }
    }

    protected function applyPriceCascade(int $productId, float $price, bool $keepSpecial, bool $skipRelations): int
    {
        $updated = 0;
        $this->applyPrice($productId, $price, $keepSpecial);
        $updated++;

        if ($skipRelations) {
            return $updated;
        }

        $product = $this->productRepository->find($productId);
        if (! $product) {
            return $updated;
        }

        if ($product->type === 'configurable') {
            foreach ($product->variants as $variant) {
                $this->applyPrice($variant->id, $price, $keepSpecial);
                $updated++;
            }
        } elseif ($product->type === 'simple' && $product->parent_id) {
            $this->applyPrice($product->parent_id, $price, $keepSpecial);
            $updated++;
        }

        return $updated;
    }

    protected function reindexPrices(array $productIds): void
    {
        $ids = implode(',', $productIds);
        $products = $this->productRepository
            ->whereIn('id', $productIds)
            ->orderByRaw("FIELD(id, $ids)")
            ->get();

        app(PriceIndexer::class)->reindexRows($products);
        $this->info('价格索引已重建');
    }

    protected function clearCustomerGroupPrices(array $productIds): void
    {
        $repo = app(ProductCustomerGroupPriceRepository::class);
        $prices = $repo->whereIn('product_id', $productIds)->get();
        $deleted = 0;
        foreach ($prices as $price) {
            $repo->delete($price->id);
            $deleted++;
        }
        $this->info('已清除客户组价格条目数：'.$deleted);
    }

    protected function clearCatalogRulePrices(array $productIds): void
    {
        $repo = app(CatalogRuleProductPriceRepository::class);
        $rows = $repo->whereIn('product_id', $productIds)->get();
        $deleted = 0;
        foreach ($rows as $row) {
            $repo->delete($row->id);
            $deleted++;
        }
        $this->info('已清除目录规则价格条目数：'.$deleted);
    }

    protected function printPriceIndices(array $productIds): void
    {
        $rows = app(\Webkul\Product\Repositories\ProductPriceIndexRepository::class)
            ->whereIn('product_id', $productIds)
            ->orderBy('product_id')
            ->get(['product_id', 'channel_id', 'customer_group_id', 'min_price', 'regular_min_price']);

        foreach ($rows as $row) {
            $this->line("PID={$row->product_id} CH={$row->channel_id} CG={$row->customer_group_id} min={$row->min_price} regular_min={$row->regular_min_price}");
        }
    }
}
