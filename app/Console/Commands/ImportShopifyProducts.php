<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Inventory\Repositories\InventorySourceRepository;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * 从Shopify导入产品到Bagisto的命令
 * Command to import products from Shopify to Bagisto
 */
class ImportShopifyProducts extends Command
{
    /**
     * # 导入单个产品
     * sudo -u www php artisan shopify:import-products https://loakia.com/products/sweetheart-hair-claw-clip-in-two-scoops --currency=USD
     * # 批量导入
     * sudo -u www php artisan shopify:import-products https://ghacnj.com --collection=zyn --currency=USD
     * @var string
    */
    protected $signature = 'shopify:import-products 
                            {shopify_url : Shopify store URL or product URL (e.g., https://loakia.com or https://loakia.com/products/handle)}
                            {--collection=* : Specific collections to import (optional)}
                            {--limit=50 : Number of products to import per collection}
                            {--currency= : Currency code to request (e.g., USD)}
                            {--dry-run : Run without actually importing}
                            {--start-page=1 : Start from specific page when fetching products}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products from Shopify store to Bagisto';

    protected $productRepository;
    protected $categoryRepository;
    protected $attributeFamilyRepository;
    protected $attributeRepository;
    protected $attributeOptionRepository;
    protected $channelRepository;
    protected $inventorySourceRepository;
    
    /**
     * 管理员token缓存
     * Admin token cache
     */
    private $adminToken = null;

    /**
     * 构造函数
     * Constructor
     */
    public function __construct(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        AttributeFamilyRepository $attributeFamilyRepository,
        AttributeRepository $attributeRepository,
        AttributeOptionRepository $attributeOptionRepository,
        ChannelRepository $channelRepository,
        InventorySourceRepository $inventorySourceRepository
    ) {
        parent::__construct();
        
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->attributeFamilyRepository = $attributeFamilyRepository;
        $this->attributeRepository = $attributeRepository;
        $this->attributeOptionRepository = $attributeOptionRepository;
        $this->channelRepository = $channelRepository;
        $this->inventorySourceRepository = $inventorySourceRepository;
    }

    /**
     * 执行命令
     * Execute the console command
     */
    public function handle()
    {
        $shopifyUrl = rtrim($this->argument('shopify_url'), '/');
        $collections = $this->option('collection');
        $limit = $this->option('limit');
        $currency = $this->option('currency');
        $startPage = (int) ($this->option('start-page') ?? 1);
        $this->info("开始从 {$shopifyUrl} 导入产品...");

        try {
            // 如果传入的是单个产品链接（/products/{handle}[.json]），直接导入该产品
            if ($this->isProductUrl($shopifyUrl)) {
                $this->info('检测到单个产品链接，开始按产品链接导入');
                $result = $this->importSingleProductByUrl($shopifyUrl, $currency);
                $this->info("导入完成! 成功: {$result['imported']}, 错误: {$result['errors']}");
                return 0;
            }

            // 获取所有集合或指定集合
            // Get all collections or specified collections
            if (empty($collections)) {
                $collections = $this->getShopifyCollections($shopifyUrl);
            }

            $totalImported = 0;
            $totalErrors = 0;
            foreach ($collections as $collection) {
                $this->info("处理集合: {$collection}");
                $result = $this->importCollectionProducts($shopifyUrl, $collection, $limit, $currency, $startPage);
                $totalImported += $result['imported'];
                $totalErrors += $result['errors'];
            }
            $this->info("导入完成! 成功: {$totalImported}, 错误: {$totalErrors}");

        } catch (\Exception $e) {
            $this->error("导入失败: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Get all collections from Shopify store
     */
    protected function getShopifyCollections($shopifyUrl)
    {
        return [
            'spinning-reel',
            'baitcasting-reels',
            'pencil-lure',
            'metal-lures',
            'hard-baits',
            'fishing-jigs',
            'soft-plastics'
        ];
        
    }

    /**
     * Import products from specified collection
     */
    protected function importCollectionProducts($shopifyUrl, $collection, $limit, $currency = null, $startPage = 1)
    {
        $imported = 0;
        $errors = 0;
        $page = max(1, (int) $startPage);

        while ($imported < $limit) {
            $productsUrl = "{$shopifyUrl}/collections/{$collection}/products.json?limit=50&page={$page}";
            if (!empty($currency)) {
                $productsUrl .= "&currency=" . urlencode($currency);
            }
            $productsUrl .= "&_ts=" . time();
            $this->info("正在获取: {$productsUrl}");
            try {
                $response = Http::timeout(60) // 增加超时时间到60秒
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                        'Accept' => 'application/json, text/plain, */*',
                        'Cache-Control' => 'no-cache',
                        'Pragma' => 'no-cache',
                    ])
                    ->get($productsUrl);
                $this->info("响应状态码: {$response->status()}");
                
                if (!$response->successful()) {
                    $this->warn("无法获取集合 {$collection} 的产品 (页面 {$page}) - 状态码: {$response->status()}");
                    break;
                }

                $data = $response->json();

                // 保存采集到的JSON：格式为 "collection名称_page.json"，保存到 storage/app/shopify/
                try {
                    $collectionSlug = Str::slug($collection, '_');
                    $fileName = $collectionSlug . '_' . $page . '.json';
                    Storage::disk('local')->put('shopify/' . $fileName, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
                    $this->info('已保存采集数据: ' . storage_path('app/shopify/' . $fileName));

                    // 保存响应头与请求信息
                    $headersPayload = [
                        'fetched_at' => date('c'),
                        'request'    => [
                            'url'     => $productsUrl,
                            'headers' => [
                                'User-Agent'    => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                                'Accept'        => 'application/json, text/plain, */*',
                                'Cache-Control' => 'no-cache',
                                'Pragma'        => 'no-cache',
                            ],
                        ],
                        'response'   => [
                            'status'  => $response->status(),
                            'headers' => $response->headers(),
                        ],
                    ];
                    $headersFile = $collectionSlug . '_' . $page . '.headers.json';
                    Storage::disk('local')->put('shopify/' . $headersFile, json_encode($headersPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
                    $this->info('已保存响应头: ' . storage_path('app/shopify/' . $headersFile));
                } catch (\Throwable $e) {
                    $this->warn('保存采集数据失败: ' . $e->getMessage());
                }
                
                if (empty($data['products'])) {
                    $this->info("集合 {$collection} 没有更多产品");
                    break;
                }
                
                $this->info("找到 " . count($data['products']) . " 个产品");

                foreach ($data['products'] as $shopifyProduct) {
                    if ($imported >= $limit) {
                        break;
                    }

                    try {
                        $this->importSingleProduct($shopifyProduct, $collection);
                        $this->info("✓ 导入产品: {$shopifyProduct['title']}");
                        $imported++;
                    } catch (\Exception $e) {
                        $this->error("✗ 导入产品失败 {$shopifyProduct['title']}: " . $e->getMessage());
                        $errors++;
                    }
                }

                $page++;
                
            } catch (\Exception $e) {
                $this->error("获取产品数据失败: " . $e->getMessage());
                $errors++;
                break;
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * 按产品链接导入单个产品
     * Import a single product by product URL
     */
    protected function importSingleProductByUrl($productUrl, $currency = null)
    {
        $imported = 0;
        $errors = 0;

        // 规范化为 .json 链接
        $jsonUrl = Str::endsWith($productUrl, '.json') ? $productUrl : ($productUrl . '.json');
        $connector = (str_contains($jsonUrl, '?')) ? '&' : '?';
        if (!empty($currency)) {
            $jsonUrl .= $connector . 'currency=' . urlencode($currency);
            $connector = '&';
        }
        $jsonUrl .= $connector . '_ts=' . time();

        $this->info("获取产品JSON: {$jsonUrl}");

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                    'Accept' => 'application/json, text/plain, */*',
                    'Cache-Control' => 'no-cache',
                    'Pragma' => 'no-cache',
                ])
                ->get($jsonUrl);

            $this->info("响应状态码: {$response->status()}");
            if (!$response->successful()) {
                $this->error('无法获取产品数据');
                return ['imported' => 0, 'errors' => 1];
            }

            $data = $response->json();

            // 保存采集数据
            try {
                $slug = Str::slug(parse_url($productUrl, PHP_URL_PATH) ?? 'product', '_');
                $fileName = 'single_' . $slug . '.json';
                Storage::disk('local')->put('shopify/' . $fileName, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
                $this->info('已保存单品采集数据: ' . storage_path('app/shopify/' . $fileName));
            } catch (\Throwable $e) {
                $this->warn('保存单品采集数据失败: ' . $e->getMessage());
            }

            // 兼容 {product: {...}} 或 {products: [...]}
            $shopifyProduct = null;
            if (!empty($data['product']) && is_array($data['product'])) {
                $shopifyProduct = $data['product'];
            } elseif (!empty($data['products']) && is_array($data['products'])) {
                $shopifyProduct = $data['products'][0] ?? null;
            }

            if (!$shopifyProduct) {
                $this->error('产品JSON中未找到有效产品数据');
                return ['imported' => 0, 'errors' => 1];
            }

            // 用 product_type 或 vendor 作为分类名，回退为 products
            $collectionName = $shopifyProduct['product_type'] ?? ($shopifyProduct['vendor'] ?? 'products');

            try {
                $this->importSingleProduct($shopifyProduct, $collectionName);
                $this->info("✓ 导入产品: {$shopifyProduct['title']}");
                $imported++;
            } catch (\Exception $e) {
                $this->error("✗ 导入产品失败 {$shopifyProduct['title']}: " . $e->getMessage());
                $errors++;
            }

        } catch (\Exception $e) {
            $this->error('获取产品JSON失败: ' . $e->getMessage());
            $errors++;
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * 判断是否为产品链接
     * Determine if URL is a Shopify product URL
     */
    protected function isProductUrl($url)
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        return (bool) preg_match('#/products/[^/]+(\.json)?$#', rtrim($path, '/'));
    }

    /**
     * 导入单个产品
     * Import a single product
     */
    protected function importSingleProduct($shopifyProduct, $collectionName)
    {
        DB::beginTransaction();
//print_r($shopifyProduct);exit();
        try {
            // Generate SKU - use hyphens instead of underscores to comply with Bagisto's slug validation rules
            $sku = $shopifyProduct['id'];
            
            // Check if product already exists
            $existingProduct = $this->productRepository->where('sku', $sku)->first();
            if ($existingProduct) {
                $this->warn("产品已存在，跳过: {$shopifyProduct['title']}");
                DB::rollBack();
                throw new \Exception("Product already exists: {$shopifyProduct['title']}");
            }

            // Get or create category
            $category = $this->getOrCreateCategory($collectionName);

            // Get default attribute family
            $attributeFamily = $this->attributeFamilyRepository->first();
            
            // Get default channel
            $channel = $this->channelRepository->first();

            // Get default inventory source
            $inventorySource = $this->inventorySourceRepository->first();

            // 处理产品变体 - 根据新规则判断产品类型
            // Handle product variants - determine product type based on new rules
            // 规则1：如果Shopify产品没有变体，则视为simple单一产品
            // Rule 1: If Shopify product has no variants, treat as simple product
            // 规则2：如果Shopify产品有变体，则视为configurable产品的子产品
            // Rule 2: If Shopify product has variants, treat as configurable product with child products
            if (!empty($shopifyProduct['variants']) && count($shopifyProduct['variants']) >= 1 && !empty($shopifyProduct['options'])) {
                // 有多个变体，创建可配置产品
                // Has multiple variants, create configurable product
                $this->createConfigurableProduct($shopifyProduct, $category, $attributeFamily, $channel, $inventorySource, $sku);
            } else {
                // 没有变体或只有一个变体，创建简单产品
                // No variants or only one variant, create simple product
                $this->createSimpleProduct($shopifyProduct, $category, $attributeFamily, $channel, $inventorySource, $sku);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 创建简单产品 - 使用Repository直接创建
     * Create simple product using Repository
     */
    protected function createSimpleProduct($shopifyProduct, $category, $attributeFamily, $channel, $inventorySource, $sku)
    {
        // 准备产品数据
        // Prepare product data
        $productData = [
            'type' => 'simple',
            'attribute_family_id' => $attributeFamily->id,
            'sku' => $sku,
            'name' => $shopifyProduct['title'],
            //'url_key' => Str::slug($shopifyProduct['title']) . '-' . time(),
            'url_key' => $shopifyProduct['handle'],
            'short_description' => $this->truncateText(strip_tags($shopifyProduct['body_html'] ?? ''), 255),
            'description' => $this->cleanHtml($shopifyProduct['body_html'] ?? ''),
            'meta_title' => $shopifyProduct['title'],
            'meta_keywords' => is_array($shopifyProduct['tags'] ?? []) ? implode(',', $shopifyProduct['tags']) : ($shopifyProduct['tags'] ?? ''),
            'meta_description' => $this->truncateText(strip_tags($shopifyProduct['body_html'] ?? ''), 160),
            'price' => $this->getProductPrice($shopifyProduct),
            'weight' => $this->getProductWeight($shopifyProduct) ?: 1,
            'status' => 1,
            'visible_individually' => 1,
            'guest_checkout' => 1,
            'featured' => 0,
            'new' => 1,
            'manage_stock' => 1,
            'categories' => [$category->id],
            'channels' => [$channel->id],
            'inventories' => [
                $inventorySource->id => $this->getProductQuantity($shopifyProduct)
            ],
            'channel' => $channel->code,
            'locale' => 'en',
        ];

        $this->info("创建简单产品: {$shopifyProduct['title']}");
        $this->info("Creating simple product: {$shopifyProduct['title']}");

        // 使用ProductRepository创建产品
        // Create product using ProductRepository
        $product = $this->productRepository->create($productData);
        
        if (!$product) {
            throw new \Exception("Failed to create simple product");
        }

        $this->info("简单产品创建成功，ID: {$product->id}");

        // 触发产品创建事件以更新flat表
        // Trigger product create event to update flat table
        Event::dispatch('catalog.product.create.after', $product);

        // 更新产品详细信息（包括属性值）
        // Update product details (including attribute values)
        // 为单变体的 simple 产品写入 Shopify 选项对应的属性
        $options = $shopifyProduct['options'] ?? [];
        $firstVariant = (!empty($shopifyProduct['variants']) && is_array($shopifyProduct['variants']))
            ? $shopifyProduct['variants'][0]
            : null;

        $attributeAssignments = [];
        if (!empty($options)) {
            foreach ($options as $index => $option) {
                $optionName = $option['name'] ?? null;
                $optionValues = $option['values'] ?? [];
                if (!$optionName || empty($optionValues)) {
                    continue;
                }

                $attribute = $this->createOrGetAttribute($optionName, $optionValues, $attributeFamily);

                $selectedValue = null;
                if ($firstVariant) {
                    $optionKey = 'option' . ($index + 1);
                    $selectedValue = $firstVariant[$optionKey] ?? null;
                }

                if (!$selectedValue) {
                    $selectedValue = $optionValues[0] ?? null;
                }

                if ($selectedValue) {
                    $attributeOption = $this->getOrCreateAttributeOption($attribute, $selectedValue);
                    $attributeAssignments[$attribute->code] = $attributeOption->id;
                }
            }
        }

        if (!empty($attributeAssignments)) {
            foreach ($attributeAssignments as $code => $optionId) {
                $productData[$code] = $optionId;
            }
            $this->info("写入简单产品属性: " . count($attributeAssignments) . " 个");
        }

        $this->productRepository->update($productData, $product->id);

        // 通过 ProductAttributeValueRepository 再次确保属性值正确保存
        if (!empty($attributeAssignments)) {
            try {
                $attributeValueRepository = app(\Webkul\Product\Repositories\ProductAttributeValueRepository::class);
                $attributes = $this->attributeRepository->findWhereIn('code', array_keys($attributeAssignments));

                $payload = $attributeAssignments;
                // 对于非 per_channel / 非 per_locale 的属性，仓库内部会处理为 NULL
                $payload['channel'] = $channel->code;
                $payload['locale'] = core()->getCurrentLocaleCode() ?? 'en';

                $attributeValueRepository->saveValues($payload, $product, $attributes);
                Event::dispatch('catalog.product.update.after', $product);
                $this->info('已通过属性值仓库保存 simple 产品的可见属性');
            } catch (\Throwable $e) {
                $this->warn('保存简单产品属性值失败（将使用主更新结果）：' . $e->getMessage());
            }
        }
        
        // 触发产品更新事件以更新flat表
        // Trigger product update event to update flat table
        Event::dispatch('catalog.product.update.after', $product);

        // 刷新产品实例
        // Refresh product instance
        $product = $this->productRepository->find($product->id);

        // 处理产品图片 - 即使图片处理失败也不影响产品创建
        // Handle product images - don't fail product creation even if image processing fails
        $imageProcessed = false;
        if (!empty($shopifyProduct['images'])) {
            try {
                $this->handleProductImages($product, $shopifyProduct['images'], true, $shopifyProduct);
                $imageProcessed = true;
                $this->info("产品图片处理成功");
            } catch (\Exception $e) {
                $this->warn("产品图片处理失败，但产品创建成功: " . $e->getMessage());
                // 不抛出异常，允许产品创建成功
                // Don't throw exception, allow product creation to succeed
            }
        } else {
            $this->info("产品没有图片，跳过图片处理");
        }

        // 记录产品创建状态
        // Log product creation status
        $this->info("简单产品 '{$shopifyProduct['title']}' 导入完成");
        if (!$imageProcessed && !empty($shopifyProduct['images'])) {
            $this->warn("注意：产品图片未能成功处理");
        }

        return $product;
    }



    /**
     * 创建可配置产品（带变体）
     * Create configurable product (with variants)
     */
    protected function createConfigurableProduct($shopifyProduct, $category, $attributeFamily, $channel, $inventorySource, $sku)
    {
        $this->info("检测到产品变体，创建可配置产品: {$shopifyProduct['title']}");
        
        try {
            // 1. Analyze Shopify product options and variants
            $options = $shopifyProduct['options'] ?? [];
            $variants = $shopifyProduct['variants'] ?? [];
            
            if (empty($options) || empty($variants)) {
                $this->warn("产品没有有效的选项或变体，创建为简单产品");
                return $this->createSimpleProduct($shopifyProduct, $category, $attributeFamily, $channel, $inventorySource, $sku);
            }
            
            // 2. 创建或获取属性
            // 2. Create or get attributes
            $superAttributes = [];
            $variantMapping = [];
            
            foreach ($options as $option) {
                $optionName = $option['name'];
                $optionValues = $option['values'];
                
                // 创建或获取属性
                // Create or get attribute
                $attribute = $this->createOrGetAttribute($optionName, $optionValues, $attributeFamily);
                
                // 创建或获取属性选项
                // Create or get attribute options
                $attributeOptions = [];
                foreach ($optionValues as $value) {
                    $attributeOption = $this->getOrCreateAttributeOption($attribute, $value);
                    $attributeOptions[] = $attributeOption->id;
                    $variantMapping[$attribute->code][$value] = $attributeOption->id;
                }
                
                $superAttributes[$attribute->code] = $attributeOptions;
                
                $this->info("属性 '{$optionName}' 创建成功，包含 " . count($attributeOptions) . " 个选项");
            }
            
            // 3. 创建可配置产品基础数据
            // 3. Create base data for configurable product
            $baseProductData = [
                'type' => 'configurable',
                'attribute_family_id' => $attributeFamily->id,
                'sku' => $sku,
                'super_attributes' => $superAttributes,
                'name' => $shopifyProduct['title'],
                //'url_key' => Str::slug($shopifyProduct['title']) . '-' . time(),
                'url_key' => $shopifyProduct['handle'],
                'description' => $this->cleanHtml($shopifyProduct['body_html'] ?? ''),
                'short_description' => $this->truncateText(strip_tags($shopifyProduct['body_html'] ?? ''), 255),
                'meta_title' => $shopifyProduct['title'],
                'meta_keywords' => implode(', ', explode(' ', $shopifyProduct['title'])),
                'meta_description' => $this->truncateText(strip_tags($shopifyProduct['body_html'] ?? ''), 160),
                'status' => 1,
                'visible_individually' => 1,
                'guest_checkout' => 1,
                'featured' => 0,
                'new' => 1,
                'weight' => 1,
                'categories' => [$category->id],
                'channels' => [$channel->id],
                'inventories' => [
                    $inventorySource->id => 0 // 可配置产品本身库存为0
                ],
                'channel' => $channel->code,
                'locale' => 'en',
            ];
            
            $this->info("创建可配置产品，包含 " . count($superAttributes) . " 个超级属性");
            
            // 4. 创建可配置产品（Bagisto会自动创建变体）
            // 4. Create configurable product (Bagisto will auto-create variants)
            $configurableProduct = $this->productRepository->create($baseProductData);
            
            if (!$configurableProduct) {
                throw new \Exception("Failed to create configurable product");
            }
            
            $this->info("可配置产品创建成功，ID: {$configurableProduct->id}");
            
            // 触发产品创建事件以更新flat表
            // Trigger product create event to update flat table
            Event::dispatch('catalog.product.create.after', $configurableProduct);
            
            // 保存产品属性值（不使用update以避免删除变体）
            // Save product attribute values (without using update to avoid deleting variants)
            $attributeValueRepository = app(\Webkul\Product\Repositories\ProductAttributeValueRepository::class);
            
            // 添加所有可编辑属性到保存列表
            // Add all editable attributes to save list
            $allEditableAttributes = $configurableProduct->attribute_family->custom_attributes
                ->merge($this->attributeRepository->findWhereIn('code', ['status', 'visible_individually', 'new', 'featured', 'guest_checkout']));
            
            $attributeValueRepository->saveValues($baseProductData, $configurableProduct, $allEditableAttributes);
            
            // 保存分类关联
            // Save category associations
            $configurableProduct->categories()->sync($baseProductData['categories']);
            
            // 手动更新status属性（确保产品启用）
            // Manually update status attribute (ensure product is enabled)
            $statusAttribute = $this->attributeRepository->findOneByField('code', 'status');
            if ($statusAttribute) {
                // Status是per_channel但不是per_locale，所以locale为NULL
                // Status is per_channel but not per_locale, so locale is NULL
                DB::table('product_attribute_values')
                    ->where('product_id', $configurableProduct->id)
                    ->where('attribute_id', $statusAttribute->id)
                    ->where('channel', $channel->code)
                    ->whereNull('locale')
                    ->update(['integer_value' => 1]);
            }
            
            // 刷新产品实例以获取最新数据
            // Refresh product instance to get latest data
            $configurableProduct = $this->productRepository->find($configurableProduct->id);
            
            $this->info("产品属性值、状态和分类已保存");
            
            // 触发产品更新事件以更新flat表
            // Trigger product update event to update flat table
            Event::dispatch('catalog.product.update.after', $configurableProduct);
            
            // 5. 更新自动创建的变体数据
            // 5. Update auto-created variant data
            $this->updateVariantsWithShopifyData($configurableProduct, $variants, $variantMapping, $channel, $inventorySource, $shopifyProduct);
            
            // 6. 处理图片
            // 6. Handle images
            if (!empty($shopifyProduct['images'])) {
                $this->handleProductImages($configurableProduct, $shopifyProduct['images'], false, $shopifyProduct);
            }
            
            return $configurableProduct;
            
        } catch (\Exception $e) {
            $this->error("创建可配置产品失败: " . $e->getMessage());
            $this->error("堆栈跟踪: " . $e->getTraceAsString());
            
            // 回退到创建简单产品
            // Fallback to creating simple product
            $this->warn("回退到创建简单产品");
            return $this->createSimpleProduct($shopifyProduct, $category, $attributeFamily, $channel, $inventorySource, $sku);
        }
    }
    
    /**
     * 更新自动创建的变体数据 - 确保configurable子产品正确关联父产品的变体信息
     * Update auto-created variant data with Shopify data - ensure configurable child products correctly associate with parent variant info
     */
    protected function updateVariantsWithShopifyData($configurableProduct, $shopifyVariants, $variantMapping, $channel, $inventorySource, $shopifyProduct = null)
    {
        $this->info("开始更新 " . count($shopifyVariants) . " 个变体的数据");
        
        // 获取自动创建的变体
        // Get auto-created variants
        $autoCreatedVariants = $configurableProduct->variants()->get();
        
        $this->info("Bagisto自动创建了 " . count($autoCreatedVariants) . " 个变体");
        
        $updatedVariantsCount = 0;
        $failedVariantsCount = 0;
        $minVariantPrice = null;
        
        // 为每个Shopify变体找到对应的Bagisto变体并更新
        // Find corresponding Bagisto variant for each Shopify variant and update
        foreach ($shopifyVariants as $shopifyVariantIndex => $shopifyVariant) {
            //print_r($shopifyVariant);exit();
            try {
                // 构建变体的属性组合
                // Build variant attribute combination
                $variantAttributes = [];
                $variantTitle = $shopifyVariant['title'] ?? $configurableProduct->name;
                
                $this->info("处理Shopify变体 #{$shopifyVariantIndex}: {$variantTitle}");
                
                // 处理选项值 - 构建属性映射，确保正确关联父产品变体信息
                // Process option values - build attribute mapping, ensure correct association with parent variant info
                for ($i = 1; $i <= 3; $i++) {
                    $optionKey = "option{$i}";
                    if (!empty($shopifyVariant[$optionKey])) {
                        $optionValue = $shopifyVariant[$optionKey];
                        
                        // 在variantMapping中查找对应的属性和选项ID
                        // Find corresponding attribute and option ID in variantMapping
                        $found = false;
                        foreach ($variantMapping as $attributeCode => $valueMapping) {
                            if (isset($valueMapping[$optionValue])) {
                                $variantAttributes[$attributeCode] = $valueMapping[$optionValue];
                                $this->info("映射选项: {$optionValue} -> 属性 {$attributeCode} (选项ID: {$valueMapping[$optionValue]})");
                                $found = true;
                                break;
                            }
                        }
                        
                        if (!$found) {
                            $this->warn("未找到选项值 '{$optionValue}' 的映射");
                        }
                    }
                }
                
                if (empty($variantAttributes)) {
                    $this->warn("Shopify变体 #{$shopifyVariantIndex} 没有有效的属性映射，跳过");
                    $failedVariantsCount++;
                    continue;
                }
                
                // 找到匹配的自动创建变体 - 确保精确匹配所有属性
                // Find matching auto-created variant - ensure exact match for all attributes
                $matchingVariant = null;
                $bestMatch = null;
                $bestMatchScore = 0;
                
                foreach ($autoCreatedVariants as $autoVariant) {
                    $matchScore = 0;
                    $totalAttributes = count($variantAttributes);
                    
                    foreach ($variantAttributes as $attrCode => $expectedOptionId) {
                        $attrValue = $autoVariant->attribute_values()
                            ->whereHas('attribute', function($q) use ($attrCode) {
                                $q->where('code', $attrCode);
                            })
                            ->first();
                        
                        if ($attrValue && $attrValue->integer_value == $expectedOptionId) {
                            $matchScore++;
                        }
                    }
                    
                    // 完全匹配优先
                    // Perfect match takes priority
                    if ($matchScore == $totalAttributes) {
                        $matchingVariant = $autoVariant;
                        break;
                    }
                    
                    // 记录最佳部分匹配
                    // Record best partial match
                    if ($matchScore > $bestMatchScore) {
                        $bestMatchScore = $matchScore;
                        $bestMatch = $autoVariant;
                    }
                }
                
                // 如果没有完全匹配，使用最佳部分匹配
                // If no perfect match, use best partial match
                if (!$matchingVariant && $bestMatch && $bestMatchScore > 0) {
                    $matchingVariant = $bestMatch;
                    $this->warn("使用部分匹配的变体 (匹配度: {$bestMatchScore}/" . count($variantAttributes) . ")");
                }
                
                if ($matchingVariant) {
                    // 获取可配置产品的名称
                    // Get configurable product name
                    $parentName = $configurableProduct->name ?? 'Product';
                    
                    // 构建变体名称 - 确保子产品名称包含父产品信息
                    // Build variant name - ensure child product name includes parent product info
                    $variantName = $parentName . ' - ' . ($shopifyVariant['title'] ?? 'Variant');
                    
                    // 获取Shopify变体的价格、重量和库存，使用优化的方法
                    // Get Shopify variant price, weight and inventory using optimized methods
                    $variantPrice = $this->getProductPrice($shopifyVariant) ?: $this->getProductPrice(['variants' => [$shopifyVariant]]);
                    $variantWeight = $this->getProductWeight($shopifyVariant) ?: $this->getProductWeight(['variants' => [$shopifyVariant]]);
                    $variantQuantity = $this->getProductQuantity($shopifyVariant) ?: $this->getProductQuantity(['variants' => [$shopifyVariant]]);
                    if($variantPrice  < 6){
                        $variantPrice = 7.99;
                    }
                    // 更新变体数据 - 确保正确关联父产品变体信息
                    // Update variant data - ensure correct association with parent variant info
                    $updateData = [
                        'name' => $variantName,
                        //'url_key' => Str::slug($variantName) . '-' . $matchingVariant->id . '-' . time(),
                        'url_key' => $configurableProduct->url_key . '-' . $matchingVariant->id,
                        'price' => $variantPrice,
                        'weight' => $variantWeight,
                        'sku' => $shopifyVariant['id'] ?? ($matchingVariant->sku . '-' . $shopifyVariantIndex),
                        'status' => 1, // 确保变体启用 / Ensure variant is enabled
                        'visible_individually' => 0, // 变体不单独显示 / Variants not individually visible
                        'parent_id' => $configurableProduct->id, // 确保正确关联父产品 / Ensure correct parent association
                        'inventories' => [
                            $inventorySource->id => $variantQuantity
                        ],
                        'channel' => $channel->code,
                        'locale' => 'en',
                    ];
                    
                    // 使用ProductRepository更新变体
                    // Update variant using ProductRepository
                    $updatedVariant = $this->productRepository->update($updateData, $matchingVariant->id);
                    
                    if ($updatedVariant) {
                        // 触发变体更新事件以更新flat表
                        // Trigger variant update event to update flat table
                        Event::dispatch('catalog.product.update.after', $updatedVariant);
                        
                        // 处理变体图片 - 为子产品添加图片
                        // Handle variant images - add images for child products
                        try {
                            $variantImages = $this->getVariantSpecificImages($shopifyVariant, $shopifyProduct);
                            if (!empty($variantImages)) {
                                $this->handleProductImages($updatedVariant, $variantImages, true, $shopifyProduct);
                                $this->info("  - 变体图片处理成功: " . count($variantImages) . " 张");
                            } else {
                                $this->info("  - 变体没有专用图片，使用父产品图片");
                            }
                        } catch (\Exception $e) {
                            $this->warn("  - 变体图片处理失败: " . $e->getMessage());
                        }
                        
                        $updatedVariantsCount++;
                        $this->info("变体更新成功: {$variantName}");
                        $this->info("  - ID: {$matchingVariant->id}");
                        $this->info("  - SKU: {$updateData['sku']}");
                        $this->info("  - 价格: {$variantPrice}");
                        $this->info("  - 库存: {$variantQuantity}");
                        $this->info("  - 父产品ID: {$configurableProduct->id}");
                        $this->info("Variant updated successfully: {$variantName}");
                        $this->info("  - ID: {$matchingVariant->id}");
                        $this->info("  - SKU: {$updateData['sku']}");
                        $this->info("  - Price: {$variantPrice}");
                        $this->info("  - Stock: {$variantQuantity}");
                        $this->info("  - Parent ID: {$configurableProduct->id}");

                        if (is_numeric($variantPrice)) {
                            if ($minVariantPrice === null) {
                                $minVariantPrice = (float) $variantPrice;
                            } else {
                                $minVariantPrice = min($minVariantPrice, (float) $variantPrice);
                            }
                        }
                    } else {
                        $failedVariantsCount++;
                        $this->error("变体更新失败: {$variantName}");
                    }
                } else {
                    $failedVariantsCount++;
                    $this->warn("未找到匹配的变体: " . json_encode($variantAttributes));
                    $this->warn("Shopify变体数据: " . json_encode([
                        'title' => $shopifyVariant['title'] ?? 'N/A',
                        'option1' => $shopifyVariant['option1'] ?? null,
                        'option2' => $shopifyVariant['option2'] ?? null,
                        'option3' => $shopifyVariant['option3'] ?? null,
                    ]));
                    $this->warn("No matching variant found: " . json_encode($variantAttributes));
                    $this->warn("Shopify variant data: " . json_encode([
                        'title' => $shopifyVariant['title'] ?? 'N/A',
                        'option1' => $shopifyVariant['option1'] ?? null,
                        'option2' => $shopifyVariant['option2'] ?? null,
                        'option3' => $shopifyVariant['option3'] ?? null,
                    ]));
                }
                
            } catch (\Exception $e) {
                $failedVariantsCount++;
                $this->error("更新变体失败: " . $e->getMessage());
                $this->error("Failed to update variant: " . $e->getMessage());
                $this->error("变体数据: " . json_encode($shopifyVariant));
            }
        }
        
        // 输出变体更新统计
        // Output variant update statistics
        $this->info("变体更新完成:");
        $this->info("  - 成功更新: {$updatedVariantsCount}");
        $this->info("  - 更新失败: {$failedVariantsCount}");
        $this->info("  - 总计: " . count($shopifyVariants));
        $this->info("Variant update completed:");
        $this->info("  - Successfully updated: {$updatedVariantsCount}");
        $this->info("  - Failed to update: {$failedVariantsCount}");
        $this->info("  - Total: " . count($shopifyVariants));
        
        if ($minVariantPrice !== null && $minVariantPrice >= 0) {
            try {
                $attributeValueRepository = app(\Webkul\Product\Repositories\ProductAttributeValueRepository::class);
                $priceAttribute = $this->attributeRepository->findOneByField('code', 'price');

                if ($priceAttribute) {
                    $attributeValueRepository->saveValues([
                        'price'   => $minVariantPrice,
                        'channel' => $channel->code,
                    ], $configurableProduct, collect([$priceAttribute]));

                    $reloaded = $this->productRepository->find($configurableProduct->id);
                    Event::dispatch('catalog.product.update.after', $reloaded);
                    $this->info("已更新可配置产品价格为最小变体价: {$minVariantPrice}");
                }
            } catch (\Throwable $e) {
                $this->warn("更新父产品价格失败: " . $e->getMessage());
            }
        }

        return $updatedVariantsCount > 0;
    }

    /**
     * 获取变体专用图片 - 为子产品获取特定的变体图片
     * Get variant specific images - get specific variant images for child products
     */
    protected function getVariantSpecificImages($shopifyVariant, $shopifyProduct)
    {
        $variantImages = [];
        
        // 优先使用变体的featured_image
        // Priority: use variant's featured_image
        if (!empty($shopifyVariant['featured_image'])) {
            $featuredImage = $shopifyVariant['featured_image'];
            
            // 直接使用featured_image作为变体图片
            // Directly use featured_image as variant image
            $variantImages[] = $featuredImage;
            $this->info("使用变体featured_image: {$featuredImage['src']}");
            
            return $variantImages;
        }
        
        // 备用方案：如果变体有专用的image_id，获取对应的图片
        // Fallback: If variant has specific image_id, get corresponding image
        if (!empty($shopifyVariant['image_id'])) {
            $imageId = $shopifyVariant['image_id'];
            
            // 在产品图片中查找对应的图片
            // Find corresponding image in product images
            if (!empty($shopifyProduct['images'])) {
                foreach ($shopifyProduct['images'] as $image) {
                    if ($image['id'] == $imageId) {
                        $variantImages[] = $image;
                        $this->info("找到变体专用图片: {$image['src']}");
                        break;
                    }
                }
            }
        }
        
        // 如果没有找到变体专用图片，使用所有产品图片
        // If no variant specific images found, use all product images
        if (empty($variantImages) && !empty($shopifyProduct['images'])) {
            $variantImages = $shopifyProduct['images'];
            $this->info("变体没有专用图片，使用所有产品图片: " . count($variantImages) . " 张");
        }
        
        return $variantImages;
    }

    /**
     * 获取图片缓存目录路径
     * Get image cache directory path
     */
    protected function getImageCacheDirectory()
    {
        $cacheDir = base_path('tmp/product_images');
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        return $cacheDir;
    }

    /**
     * 生成图片缓存文件名
     * Generate image cache filename
     */
    protected function generateImageCacheFilename($imageUrl, $mimeType = null)
    {
        $urlHash = md5($imageUrl);
        $extension = 'jpg'; // 默认扩展名
        
        if ($mimeType) {
            $extension = match($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'jpg'
            };
        }
        
        return $urlHash . '.' . $extension;
    }

    /**
     * 下载并缓存图片
     * Download and cache image
     */
    protected function downloadAndCacheImage($imageUrl)
    {
        $cacheDir = $this->getImageCacheDirectory();
        
        // 首先尝试下载图片以获取MIME类型
        // First try to download image to get MIME type
        $maxRetries = 3;
        $imageContent = null;
        $mimeType = null;
        
        for ($retry = 0; $retry < $maxRetries; $retry++) {
            try {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                        'Accept' => 'image/*,*/*;q=0.8',
                    ])
                    ->get($imageUrl);
                
                if ($response->successful()) {
                    $imageContent = $response->body();
                    
                    // 检测图片类型
                    // Detect image type
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->buffer($imageContent);
                    break;
                } else {
                    $this->warn("图片下载失败 (尝试 " . ($retry + 1) . "/{$maxRetries}): HTTP {$response->status()} - {$imageUrl}");
                }
            } catch (\Exception $e) {
                $this->warn("图片下载异常 (尝试 " . ($retry + 1) . "/{$maxRetries}): {$e->getMessage()} - {$imageUrl}");
            }
            
            if ($retry < $maxRetries - 1) {
                sleep(1); // 重试前等待1秒
            }
        }

        if (!$imageContent) {
            throw new \Exception("Failed to download image after {$maxRetries} attempts: {$imageUrl}");
        }

        // 验证图片内容
        // Validate image content
        if (strlen($imageContent) < 100) {
            throw new \Exception("Image content too small, may not be valid image: {$imageUrl}");
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedMimes)) {
            throw new \Exception("Unsupported image type {$mimeType}: {$imageUrl}");
        }

        // 生成缓存文件名并保存
        // Generate cache filename and save
        $cacheFilename = $this->generateImageCacheFilename($imageUrl, $mimeType);
        $cachePath = $cacheDir . '/' . $cacheFilename;
        
        if (file_put_contents($cachePath, $imageContent) === false) {
            throw new \Exception("Failed to save image to cache: {$cachePath}");
        }

        return [
            'content' => $imageContent,
            'mime_type' => $mimeType,
            'cache_path' => $cachePath,
            'size' => strlen($imageContent)
        ];
    }

    /**
     * 获取缓存的图片或下载新图片
     * Get cached image or download new image
     */
    protected function getCachedOrDownloadImage($imageUrl)
    {
        $cacheDir = $this->getImageCacheDirectory();
        
        // 尝试所有可能的扩展名来查找缓存文件
        // Try all possible extensions to find cached file
        $urlHash = md5($imageUrl);
        $extensions = ['jpg', 'png', 'gif', 'webp'];
        
        foreach ($extensions as $ext) {
            $cacheFilename = $urlHash . '.' . $ext;
            $cachePath = $cacheDir . '/' . $cacheFilename;
            
            if (file_exists($cachePath) && filesize($cachePath) > 100) {
                // 从缓存读取
                // Read from cache
                $imageContent = file_get_contents($cachePath);
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($imageContent);
                
                $this->info("使用缓存图片: {$cacheFilename}");
                
                return [
                    'content' => $imageContent,
                    'mime_type' => $mimeType,
                    'cache_path' => $cachePath,
                    'size' => strlen($imageContent),
                    'from_cache' => true
                ];
            }
        }
        
        // 缓存中没有找到，下载新图片
        // Not found in cache, download new image
        $this->info("缓存中未找到图片，开始下载: {$imageUrl}");
        
        $result = $this->downloadAndCacheImage($imageUrl);
        $result['from_cache'] = false;
        
        return $result;
    }

    /**
     * 为simple产品获取变体图片
     * Get variant images for simple product
     */
    protected function getVariantImagesForSimpleProduct($shopifyProduct)
    {
        $images = $shopifyProduct['images'] ?? [];
        
        // 如果产品有变体，尝试获取第一个变体的图片
        // If product has variants, try to get first variant's images
        if (!empty($shopifyProduct['variants']) && is_array($shopifyProduct['variants'])) {
            $firstVariant = $shopifyProduct['variants'][0];
            
            // 如果变体有特定的图片ID，查找对应的图片
            // If variant has specific image ID, find corresponding image
            if (!empty($firstVariant['image_id'])) {
                foreach ($images as $image) {
                    if ($image['id'] == $firstVariant['image_id']) {
                        $this->info("为simple产品使用变体专用图片: {$image['src']}");
                        return [$image]; // 返回单个图片数组
                    }
                }
            }
        }
        
        // 如果没有找到变体专用图片，返回所有产品图片
        // If no variant-specific image found, return all product images
        return $images;
    }

    /**
     * 处理产品图片 - 优化性能，支持变体图片和缓存机制
     * Handle product images - optimized performance, supports variant images and caching mechanism
     */
    protected function handleProductImages($product, $images, $isSimpleProduct = false, $shopifyProduct = null)
    {
        // 如果没有传入图片且是simple产品，尝试使用变体图片
        // If no images passed and it's a simple product, try to use variant images
        if (empty($images) && $isSimpleProduct && $shopifyProduct) {
            $images = $this->getVariantImagesForSimpleProduct($shopifyProduct);
        }
        
        if (empty($images)) {
            $this->warn("产品 {$product->name} 没有图片数据");
            return false; // 返回false表示没有成功处理图片
        }

        $this->info("开始处理 " . count($images) . " 张产品图片");

        // 使用优化的批量处理方法
        // Use optimized batch processing method
        $result = $this->processImagesInBatches($product, $images);
        
        $this->info("图片处理完成: 成功 {$result['success']} 张, 失败 {$result['fail']} 张, 缓存命中 {$result['cache_hits']} 张");

        // 如果所有图片都失败了，返回false
        // Return false if all images failed
        return $result['success'] > 0;
    }

    /**
     * 优化的图片下载方法 - 性能优化，减少阻塞时间
     * Optimized image download method - performance optimization to reduce blocking time
     */
    protected function downloadImageOptimized($imageUrl, $maxRetries = 3)
    {
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            $attempt++;
            
            try {
                // 使用更短的超时时间和优化的请求头
                $response = Http::timeout(10) // 减少超时时间
                    ->connectTimeout(5) // 连接超时
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                        'Accept' => 'image/*,*/*;q=0.8',
                        'Accept-Encoding' => 'gzip, deflate',
                        'Connection' => 'close', // 避免保持连接
                    ])
                    ->get($imageUrl);
                
                if ($response->successful()) {
                    $imageContent = $response->body();
                    
                    // 快速验证图片内容
                    if (strlen($imageContent) < 100) {
                        throw new \Exception("Image content too small");
                    }
                    
                    // 检测图片类型
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->buffer($imageContent);
                    
                    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($mimeType, $allowedMimes)) {
                        throw new \Exception("Unsupported image type: {$mimeType}");
                    }
                    
                    return [
                        'content' => $imageContent,
                        'mime_type' => $mimeType,
                        'size' => strlen($imageContent),
                        'from_cache' => false
                    ];
                } else {
                    throw new \Exception("HTTP {$response->status()}");
                }
            } catch (\Exception $e) {
                if ($attempt < $maxRetries) {
                    // 指数退避重试策略，但限制最大延迟
                    $delay = min(pow(2, $attempt - 1), 3); // 最大延迟3秒
                    sleep($delay);
                } else {
                    throw $e;
                }
            }
        }
        
        throw new \Exception("Failed to download image after {$maxRetries} attempts");
    }

    /**
     * 批量处理图片 - 性能优化，减少内存占用
     * Batch process images - performance optimization to reduce memory usage
     */
    protected function processImagesInBatches($product, $images, $batchSize = 5)
    {
        $productImageRepository = app(\Webkul\Product\Repositories\ProductImageRepository::class);
        $totalSuccess = 0;
        $totalFail = 0;
        $totalCacheHits = 0;
        
        // 分批处理图片，避免内存占用过大
        $batches = array_chunk($images, $batchSize);
        
        foreach ($batches as $batchIndex => $batch) {
            $this->info("处理图片批次 " . ($batchIndex + 1) . "/" . count($batches));
            
            $batchSuccess = 0;
            $batchFail = 0;
            $batchCacheHits = 0;
            
            foreach ($batch as $index => $imageData) {
                try {
                    $imageUrl = $imageData['src'] ?? null;
                    if (!$imageUrl || !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                        $batchFail++;
                        continue;
                    }
                    
                    // 使用缓存机制获取图片
                    $imageResult = $this->getCachedOrDownloadImage($imageUrl);
                    
                    if ($imageResult['from_cache']) {
                        $batchCacheHits++;
                    }
                    
                    // 生成文件名和路径
                    $extension = match($imageResult['mime_type']) {
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/gif' => 'gif',
                        'image/webp' => 'webp',
                        default => 'jpg'
                    };
                    
                    $directory = 'product/' . $product->id;
                    $globalIndex = ($batchIndex * $batchSize) + $index + 1;
                    $filename = 'image_' . $globalIndex . '_' . time() . '.' . $extension;
                    $path = $directory . '/' . $filename;
                    
                    // 保存图片
                    if (!Storage::put($path, $imageResult['content'])) {
                        $batchFail++;
                        continue;
                    }
                    
                    // 创建产品图片记录
                    $productImage = $productImageRepository->create([
                        'product_id' => $product->id,
                        'type' => 'images',
                        'path' => $path,
                        'position' => $globalIndex
                    ]);
                    
                    if ($productImage) {
                        $batchSuccess++;
                    } else {
                        Storage::delete($path);
                        $batchFail++;
                    }
                    
                    // 释放内存
                    unset($imageResult);
                    
                } catch (\Exception $e) {
                    $batchFail++;
                }
            }
            
            $totalSuccess += $batchSuccess;
            $totalFail += $batchFail;
            $totalCacheHits += $batchCacheHits;
            
            $this->info("批次 " . ($batchIndex + 1) . " 完成: 成功 {$batchSuccess}, 失败 {$batchFail}, 缓存 {$batchCacheHits}");
            
            // 强制垃圾回收，释放内存
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
        
        return [
            'success' => $totalSuccess,
            'fail' => $totalFail,
            'cache_hits' => $totalCacheHits
        ];
    }
    protected function getOrCreateCategory($collectionName)
    {
        // 查找现有分类
        // Find existing category
        $category = $this->categoryRepository->whereTranslation('name', $this->formatCategoryName($collectionName))->first();
        
        if (!$category) {
            // 创建新分类
            // Create new category
            $categoryData = [
                'parent_id' => 1, // 假设1是根分类 / Assume 1 is root category
                'position' => 1,
                'status' => 1,
                'display_mode' => 'products_and_description',
                'name' => $this->formatCategoryName($collectionName),
                'slug' => Str::slug($collectionName),
                'description' => "Products from {$collectionName} collection",
                'meta_title' => $this->formatCategoryName($collectionName),
                'meta_description' => "Browse our {$collectionName} collection",
                'meta_keywords' => $collectionName,
            ];

            $category = $this->categoryRepository->create($categoryData);
        }

        return $category;
    }

    /**
     * 格式化分类名称
     * Format category name
     */
    protected function formatCategoryName($collectionName)
    {
        return ucwords(str_replace(['-', '_'], ' ', $collectionName));
    }

    /**
     * 获取产品价格 - 优化价格获取逻辑，确保必须导入准确的产品价格
     * Get product price - optimized price logic to ensure accurate product prices are imported
     */
    protected function getProductPrice($shopifyProduct)
    {
        $price = 0.00;
        
        // 优先从variants中获取价格
        // Priority: get price from variants
        if (!empty($shopifyProduct['variants']) && is_array($shopifyProduct['variants'])) {
            foreach ($shopifyProduct['variants'] as $variant) {
                if (isset($variant['price']) && is_numeric($variant['price'])) {

                    $variantPrice = (float) $variant['price'];
                    if($variantPrice < 6){
                        $variantPrice = 7.99;
                    }
                    
                    if ($variantPrice > 0) {
                        $price = $variantPrice;
                        break; // 使用第一个有效价格
                    }
                }
            }
            
            // 如果第一个变体没有有效价格，尝试找到最低价格
            // If first variant has no valid price, try to find minimum price
            if ($price <= 0) {
                $validPrices = [];
                foreach ($shopifyProduct['variants'] as $variant) {
                    if (isset($variant['price']) && is_numeric($variant['price'])) {
                        $variantPrice = (float) $variant['price'];
                        if ($variantPrice > 0) {
                            $validPrices[] = $variantPrice;
                        }
                    }
                }
                
                if (!empty($validPrices)) {
                    $price = min($validPrices); // 使用最低价格
                    $this->info("使用最低变体价格: " . number_format($price, 2));
                    $this->info("Using minimum variant price: " . number_format($price, 2));
                }
            }
        }
        
        // 如果variants中没有有效价格，尝试从产品级别获取
        // If no valid price in variants, try to get from product level
        if ($price <= 0) {
            if (isset($shopifyProduct['price']) && is_numeric($shopifyProduct['price'])) {
                $price = (float) $shopifyProduct['price'];
            }
        }
        
        // 验证价格有效性
        // Validate price validity
        if ($price <= 0) {
            $this->warn("产品 '{$shopifyProduct['title']}' 没有有效价格，使用默认价格 1.00");
            $this->warn("Product '{$shopifyProduct['title']}' has no valid price, using default price 1.00");
            $price = 1.00; // 设置最小默认价格
        }
        
        // 确保价格格式正确（最多2位小数）
        // Ensure price format is correct (max 2 decimal places)
        $price = round($price, 2);
        
        $this->info("产品价格: " . number_format($price, 2));
        $this->info("Product price: " . number_format($price, 2));
        
        return $price;
    }

    /**
     * 获取产品重量 - 优化重量获取逻辑
     * Get product weight - optimized weight logic
     */
    protected function getProductWeight($shopifyProduct)
    {
        $weight = 0;
        
        // 优先从variants中获取重量
        // Priority: get weight from variants
        if (!empty($shopifyProduct['variants']) && is_array($shopifyProduct['variants'])) {
            foreach ($shopifyProduct['variants'] as $variant) {
                if (isset($variant['weight']) && is_numeric($variant['weight'])) {
                    $variantWeight = (float) $variant['weight'];
                    if ($variantWeight > 0) {
                        $weight = $variantWeight;
                        break; // 使用第一个有效重量
                    }
                }
            }
        }
        
        // 如果variants中没有有效重量，尝试从产品级别获取
        // If no valid weight in variants, try to get from product level
        if ($weight <= 0) {
            if (isset($shopifyProduct['weight']) && is_numeric($shopifyProduct['weight'])) {
                $weight = (float) $shopifyProduct['weight'];
            }
        }
        
        // 如果仍然没有重量，使用默认值
        // If still no weight, use default value
        if ($weight <= 0) {
            $weight = 1.0; // 默认重量1kg
            $this->info("产品 '{$shopifyProduct['title']}' 没有重量信息，使用默认重量 1.0");
        }
        
        return round($weight, 3); // 保留3位小数
    }

    /**
     * 获取产品库存 - 实现库存处理规则：优先获取实际库存数据，无法获取时使用1000作为默认值
     * Get product quantity - implement inventory rules: prioritize actual inventory data, use 1000 as default when unavailable
     */
    protected function getProductQuantity($shopifyProduct)
    {
        $quantity = 0;
        $hasValidInventory = false;
        
        // 优先从variants中获取库存
        // Priority: get inventory from variants
        if (!empty($shopifyProduct['variants']) && is_array($shopifyProduct['variants'])) {
            foreach ($shopifyProduct['variants'] as $variant) {
                // 检查inventory_quantity字段
                // Check inventory_quantity field
                if (isset($variant['inventory_quantity']) && is_numeric($variant['inventory_quantity'])) {
                    $variantQuantity = (int) $variant['inventory_quantity'];
                    if ($variantQuantity >= 0) { // 允许0库存，这是有效的库存数据
                        $quantity = $variantQuantity;
                        $hasValidInventory = true;
                        $this->info("从变体获取库存: {$quantity}");
                        break; // 使用第一个有效库存
                    }
                }
                
                // 如果没有inventory_quantity，检查quantity字段
                // If no inventory_quantity, check quantity field
                if (!$hasValidInventory && isset($variant['quantity']) && is_numeric($variant['quantity'])) {
                    $variantQuantity = (int) $variant['quantity'];
                    if ($variantQuantity >= 0) {
                        $quantity = $variantQuantity;
                        $hasValidInventory = true;
                        $this->info("从变体quantity字段获取库存: {$quantity}");
                        break;
                    }
                }
            }
            
            // 如果第一个变体没有有效库存，尝试汇总所有变体库存
            // If first variant has no valid inventory, try to sum all variant inventories
            if (!$hasValidInventory) {
                $totalQuantity = 0;
                $validVariants = 0;
                
                foreach ($shopifyProduct['variants'] as $variant) {
                    if (isset($variant['inventory_quantity']) && is_numeric($variant['inventory_quantity'])) {
                        $variantQuantity = (int) $variant['inventory_quantity'];
                        if ($variantQuantity >= 0) {
                            $totalQuantity += $variantQuantity;
                            $validVariants++;
                        }
                    } elseif (isset($variant['quantity']) && is_numeric($variant['quantity'])) {
                        $variantQuantity = (int) $variant['quantity'];
                        if ($variantQuantity >= 0) {
                            $totalQuantity += $variantQuantity;
                            $validVariants++;
                        }
                    }
                }
                
                if ($validVariants > 0) {
                    $quantity = $totalQuantity;
                    $hasValidInventory = true;
                    $this->info("汇总所有变体库存: {$quantity} (来自 {$validVariants} 个变体)");
                }
            }
        }
        
        // 如果variants中没有有效库存，尝试从产品级别获取
        // If no valid inventory in variants, try to get from product level
        if (!$hasValidInventory) {
            if (isset($shopifyProduct['inventory_quantity']) && is_numeric($shopifyProduct['inventory_quantity'])) {
                $quantity = (int) $shopifyProduct['inventory_quantity'];
                if ($quantity >= 0) {
                    $hasValidInventory = true;
                    $this->info("从产品级别获取库存: {$quantity}");
                }
            } elseif (isset($shopifyProduct['quantity']) && is_numeric($shopifyProduct['quantity'])) {
                $quantity = (int) $shopifyProduct['quantity'];
                if ($quantity >= 0) {
                    $hasValidInventory = true;
                    $this->info("从产品quantity字段获取库存: {$quantity}");
                }
            }
        }
        
        // 如果仍然没有有效库存数据，使用默认值1000
        // If still no valid inventory data, use default value 1000
        if (!$hasValidInventory) {
            $quantity = 1000; // 按照规则使用1000作为默认库存
            $this->warn("产品 '{$shopifyProduct['title']}' 没有有效库存数据，使用默认库存 1000");
        }
        
        // 确保库存不为负数
        // Ensure inventory is not negative
        $quantity = max(0, $quantity);
        
        $this->info("最终产品库存: {$quantity}");
        
        return $quantity;
    }


    /**
     * 截断文本
     * Truncate text
     */
    protected function truncateText($text, $length)
    {
        $text = strip_tags($text);
        return Str::limit($text, $length);
    }

    /**
     * 创建或获取属性
     * Create or get attribute
     */
    protected function createOrGetAttribute($optionName, $optionValues, $attributeFamily)
    {
        // 规范化属性代码
        // Normalize attribute code
        $attributeCode = Str::slug(strtolower($optionName), '_');
        
        // 检查属性是否已存在
        // Check if attribute already exists
        $attribute = $this->attributeRepository->findOneByField('code', $attributeCode);
        
        if ($attribute) {
            $this->info("属性已存在: {$optionName} (code: {$attributeCode})");
            $this->info("Attribute already exists: {$optionName} (code: {$attributeCode})");

            try {
                $group = $attributeFamily->attribute_groups()->orderBy('position')->first();

                if ($group) {
                    $exists = DB::table('attribute_group_mappings')
                        ->join('attribute_groups', 'attribute_groups.id', '=', 'attribute_group_mappings.attribute_group_id')
                        ->where('attribute_group_mappings.attribute_id', $attribute->id)
                        ->where('attribute_groups.attribute_family_id', $attributeFamily->id)
                        ->exists();

                    if (! $exists) {
                        $position = (int) DB::table('attribute_group_mappings')
                            ->where('attribute_group_id', $group->id)
                            ->max('position');

                        $group->custom_attributes()->save($attribute, ['position' => $position + 1]);
                    }
                }

                // 确保现有属性在前台可见、用于平表与筛选，并使用 swatch 展示
                $needsUpdate = false;
                $updatePayload = [];
                if (! $attribute->is_visible_on_front) {
                    $updatePayload['is_visible_on_front'] = true;
                    $needsUpdate = true;
                }
                if (! $attribute->use_in_flat) {
                    $updatePayload['use_in_flat'] = true;
                    $needsUpdate = true;
                }
                if (! $attribute->is_filterable) {
                    $updatePayload['is_filterable'] = true;
                    $needsUpdate = true;
                }
                if (! $attribute->is_configurable) {
                    $updatePayload['is_configurable'] = true;
                    $needsUpdate = true;
                }
                if (empty($attribute->swatch_type) || $attribute->swatch_type === 'dropdown') {
                    $updatePayload['swatch_type'] = 'text';
                    $needsUpdate = true;
                }
                if ($needsUpdate) {
                    $attribute->update($updatePayload);
                    $this->info("已更新属性可见性/筛选/平表设置: {$attribute->code}");
                }
            } catch (\Throwable $th) {}

            return $attribute;
        }

        // 创建新属性
        // Create new attribute
        $attributeData = [
            'code' => $attributeCode,
            'admin_name' => $optionName,
            'type' => 'select',
            'is_required' => false,
            'is_unique' => false,
            'is_filterable' => true,
            'is_configurable' => true,
            'is_user_defined' => true,
            'is_visible_on_front' => true,
            'value_per_locale' => false,
            'value_per_channel' => false,
            'position' => 1,
            'swatch_type' => 'text',
            'use_in_flat' => true,
            'is_comparable' => false,
        ];

        $this->info("创建新属性: {$optionName} (code: {$attributeCode})");
        $this->info("Creating new attribute: {$optionName} (code: {$attributeCode})");

        $attribute = $this->attributeRepository->create($attributeData);
        
        $this->info("属性创建成功: {$optionName} (ID: {$attribute->id})");

        try {
            $group = $attributeFamily->attribute_groups()->orderBy('position')->first();

            if ($group) {
                $position = (int) DB::table('attribute_group_mappings')
                    ->where('attribute_group_id', $group->id)
                    ->max('position');

                $group->custom_attributes()->save($attribute, ['position' => $position + 1]);
            }
        } catch (\Throwable $th) {}

        return $attribute;
    }

    /**
     * 获取或创建属性选项
     * Get or create attribute option
     */
    protected function getOrCreateAttributeOption($attribute, $optionValue)
    {
        // 刷新属性以获取最新的选项
        // Refresh attribute to get latest options
        $attribute = $this->attributeRepository->find($attribute->id);
        
        // 查找现有选项
        // Find existing option
        $option = $attribute->options()->where('admin_name', $optionValue)->first();
        
        if ($option) {
            $this->info("选项已存在: {$optionValue}");

            // 确保所有站点语言都有翻译
            try {
                $locales = collect(core()->getAllLocales())->pluck('code')->all();
            } catch (\Throwable $e) {
                $locales = ['en'];
            }

            $existingTranslations = DB::table('attribute_option_translations')
                ->where('attribute_option_id', $option->id)
                ->pluck('locale')
                ->all();

            $missingLocales = array_diff($locales, $existingTranslations);
            if (!empty($missingLocales)) {
                $rows = [];
                foreach ($missingLocales as $locale) {
                    $rows[] = [
                        'attribute_option_id' => $option->id,
                        'locale' => $locale,
                        'label' => $optionValue,
                    ];
                }
                if (!empty($rows)) {
                    DB::table('attribute_option_translations')->insert($rows);
                    $this->info("为选项补充翻译: " . implode(',', $missingLocales));
                }
            }

            return $option;
        }

        // 创建新选项
        // Create new option
        $optionData = [
            'attribute_id' => $attribute->id,
            'admin_name' => $optionValue,
            'sort_order' => $attribute->options()->count() + 1,
        ];

        $this->info("为属性 {$attribute->admin_name} 创建新选项: {$optionValue}");

        $option = $this->attributeOptionRepository->create($optionData);
        
        // 创建多语言标签（覆盖所有已配置语言）
        // Create multilingual labels for all configured locales
        try {
            $locales = collect(core()->getAllLocales())->pluck('code')->all();
        } catch (\Throwable $e) {
            $locales = ['en'];
        }

        $rows = [];
        foreach ($locales as $locale) {
            $rows[] = [
                'attribute_option_id' => $option->id,
                'locale' => $locale,
                'label' => $optionValue,
            ];
        }
        DB::table('attribute_option_translations')->insert($rows);

        return $option;
    }

    /**
     * 清理HTML内容，保留基本标签但移除所有属性
     * Clean HTML content, keep basic tags but remove all attributes
     */
    private function cleanHtml($html)
    {
        if (empty($html)) {
            return '';
        }

        // 解码Unicode转义序列
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // 移除所有HTML标签的属性，只保留标签本身
        $cleanHtml = preg_replace('/<(\w+)[^>]*>/', '<$1>', $html);
        
        return $cleanHtml;
    }

}
