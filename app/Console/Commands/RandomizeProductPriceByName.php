<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Webkul\Product\Models\ProductFlat;
use Webkul\Product\Repositories\ProductRepository;

class RandomizeProductPriceByName extends Command
{
    //php artisan product:randomize-price-by-name "Big Effing Clip in Toasted Sugar" --min=20 --max=23 --dry-run
    protected $signature = 'product:randomize-price-by-name 
                            {name : Product name to match}
                            {--min= : Minimum integer price}
                            {--max= : Maximum integer price}
                            {--dry-run : Preview without saving}';

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
            $this->applyPrice($productId, $price);
            $updated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('已更新记录数：'.$updated);

        return self::SUCCESS;
    }

    protected function applyPrice(int $productId, float $price): void
    {
        $this->productRepository->update([
            'price' => $price,
        ], $productId, ['price']);

        $product = $this->productRepository->find($productId);
        if ($product) {
            Event::dispatch('catalog.product.update.after', $product);
        }
    }
}
