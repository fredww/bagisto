<?php
// 试运行命令
// php artisan product:mass-update-price --operator=lt --price=10 --new=39.99 --dry-run
// 正式更新命令
// php artisan product:mass-update-price --operator=lt --price=10 --new=39.99
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Product\Repositories\ProductRepository;

class MassUpdateProductPrice extends Command
{
    protected $signature = 'product:mass-update-price 
                            {--channel= : Channel code}
                            {--operator=lt : Operator lt|lte|gt|gte|eq|neq}
                            {--price= : Threshold price}
                            {--new= : New price}
                            {--dry-run : Preview without saving}';

    protected $description = 'Mass update product prices by condition, affecting configurable and variants';

    public function __construct(
        protected ProductRepository $productRepository,
        protected ChannelRepository $channelRepository,
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $operator = strtolower($this->option('operator') ?? 'lt');
        $threshold = $this->option('price');
        $newPrice = $this->option('new');
        $channelCode = $this->option('channel') ?? core()->getCurrentChannel()->code;

        if ($threshold === null || $newPrice === null) {
            $this->error('Options --price and --new are required');
            return self::FAILURE;
        }

        $ops = [
            'lt' => '<',
            'lte' => '<=',
            'gt' => '>',
            'gte' => '>=',
            'eq' => '=',
            'neq' => '<>',
        ];

        if (! isset($ops[$operator])) {
            $this->error('Invalid operator. Use lt|lte|gt|gte|eq|neq');
            return self::FAILURE;
        }

        $newPrice = round((float) $newPrice, 4);
        $threshold = (float) $threshold;

        $channel = $this->channelRepository->findWhere(['code' => $channelCode])->first();
        if (! $channel) {
            $this->error('Channel not found: '.$channelCode);
            return self::FAILURE;
        }

        $customerGroup = app(\Webkul\Customer\Repositories\CustomerRepository::class)->getCurrentGroup();

        $query = DB::table('product_price_indices')
            ->join('products', 'products.id', '=', 'product_price_indices.product_id')
            ->select('products.id', 'products.type', 'products.parent_id')
            ->where('product_price_indices.channel_id', $channel->id)
            ->where('product_price_indices.customer_group_id', $customerGroup->id)
            ->where('product_price_indices.min_price', $ops[$operator], $threshold);

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('No products match the condition');
            return self::SUCCESS;
        }

        $this->info('Matched products: '.$total);

        if ($this->option('dry-run')) {
            $examples = (clone $query)->limit(10)->get();
            foreach ($examples as $row) {
                $this->line("#{$row->id} type={$row->type} parent={$row->parent_id}");
            }
            $this->line('Dry-run mode: no changes applied');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;

        (clone $query)->orderBy('products.id')->chunk(500, function ($rows) use (&$updated, $bar, $newPrice) {
            foreach ($rows as $row) {
                $product = $this->productRepository->find($row->id);
                if (! $product) {
                    $bar->advance();
                    continue;
                }

                $this->applyPrice($product->id, $newPrice);
                $updated++;

                if ($product->type === 'configurable') {
                    foreach ($product->variants as $variant) {
                        $this->applyPrice($variant->id, $newPrice);
                        $updated++;
                    }
                } elseif ($product->type === 'simple' && $product->parent_id) {
                    $this->applyPrice($product->parent_id, $newPrice);
                    $updated++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Updated records: '.$updated);

        return self::SUCCESS;
    }

    protected function applyPrice(int $productId, float $price): void
    {
        $this->productRepository->update([
            'price' => $price,
        ], $productId, ['price']);

        $product = $this->productRepository->find($productId);
        Event::dispatch('catalog.product.update.after', $product);
    }
}