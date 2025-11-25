<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\Product\Repositories\ProductFlatRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Product\ProductImage;
use Webkul\Attribute\Repositories\AttributeOptionRepository;

/**
 * sudo -u www php artisan google:generate-feed --channel=default --locale=en --output=public/feeds.xml --base-url=https://kiaoa.com
 * sudo -u www php artisan google:generate-feed --channel=default --locale=en --output=public/zyn.xml --base-url=https://kiaoa.com --category=34
 * sudo -u www php artisan google:generate-feed --channel=default --locale=en --output=public/feeds.xml --base-url=https://kiaoa.com --exclude-category=34
 * 生成 Google Merchant Center Feed 的命令
 * Command to generate Google Merchant Center Feed
 */
class GenerateGoogleFeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google:generate-feed 
                            {--channel= : Channel code (default: current channel)}
                            {--locale=en : Locale code (default: en)}
                            {--output=public/google-feed.xml : Output file path}
                            {--category= : Category ID(s), comma-separated}
                            {--exclude-category= : Exclude Category ID(s), comma-separated}
                            {--base-url= : Base URL for product links (default: from config)}
                            {--brand= : Override g:brand}
                            {--age-group= : Override g:age_group}
                            {--gender= : Override g:gender}
                            {--google-product-category= : Override g:google_product_category}
                            {--product-type= : Override g:product_type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Google Merchant Center product feed XML file';

    protected $productFlatRepository;
    protected $channelRepository;
    protected $productImage;
    protected $attributeOptionRepository;
    protected $overrideBrand;
    protected $overrideAgeGroup;
    protected $overrideGender;
    protected $overrideGoogleProductCategory;
    protected $overrideProductType;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        ProductFlatRepository $productFlatRepository,
        ChannelRepository $channelRepository,
        ProductImage $productImage,
        AttributeOptionRepository $attributeOptionRepository
    ) {
        parent::__construct();
        
        $this->productFlatRepository = $productFlatRepository;
        $this->channelRepository = $channelRepository;
        $this->productImage = $productImage;
        $this->attributeOptionRepository = $attributeOptionRepository;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $channelCode = $this->option('channel') ?? core()->getCurrentChannel()->code;
        $locale = $this->option('locale');
        $outputOption = $this->option('output') ?? 'public/google-feed.xml';
        $path = $this->resolveOutputPath($outputOption);
        $baseUrl = $this->option('base-url') ?? config('app.url');
        $categoryOption = $this->option('category');
        $excludeCategoryOption = $this->option('exclude-category');
        $this->overrideBrand = $this->option('brand') ?: null;
        $this->overrideAgeGroup = $this->option('age-group') ?: null;
        $this->overrideGender = $this->option('gender') ?: null;
        $this->overrideGoogleProductCategory = $this->option('google-product-category') ?: null;
        $this->overrideProductType = $this->option('product-type') ?: null;

        $this->info("开始生成 Google Feed...");
        $this->info("Channel: {$channelCode}");
        $this->info("Locale: {$locale}");
        $this->info("Base URL: {$baseUrl}");

        try {
            // 获取渠道
            $channel = $this->channelRepository->findWhere(['code' => $channelCode])->first();
            
            if (!$channel) {
                $this->error("渠道 '{$channelCode}' 不存在");
                return Command::FAILURE;
            }

            // 获取所有启用的、可见的产品（可选按分类过滤）
            $query = $this->productFlatRepository
                ->where('channel', $channelCode)
                ->where('locale', $locale)
                ->where('status', 1)
                ->where('visible_individually', 1)
                ->whereNull('parent_id')
                ->with([
                    'product' => function($query) {
                        $query->with(['variants', 'super_attributes', 'inventories', 'categories', 'images', 'parent.images']);
                    }
                ]);

            if (! empty($categoryOption)) {
                $categoryIds = array_values(array_filter(array_map('intval', explode(',', $categoryOption))));

                if (! empty($categoryIds)) {
                    $this->info('Filter by Category IDs: ' . implode(',', $categoryIds));

                    $query = $query->whereHas('product', function ($q) use ($categoryIds) {
                        $q->whereHas('categories', function ($sub) use ($categoryIds) {
                            $sub->whereIn('categories.id', $categoryIds);
                        });
                    });
                }
            }

            if (! empty($excludeCategoryOption)) {
                $excludeCategoryIds = array_values(array_filter(array_map('intval', explode(',', $excludeCategoryOption))));

                if (! empty($excludeCategoryIds)) {
                    $this->info('Exclude Category IDs: ' . implode(',', $excludeCategoryIds));

                    $query = $query->whereDoesntHave('product', function ($q) use ($excludeCategoryIds) {
                        $q->whereHas('categories', function ($sub) use ($excludeCategoryIds) {
                            $sub->whereIn('categories.id', $excludeCategoryIds);
                        });
                    });
                }
            }

            $products = $query->get();

            $this->info("找到 " . $products->count() . " 个产品");

            // 生成 XML
            $xml = $this->generateXML($products, $baseUrl, $channelCode, $locale);

            // 保存文件至 public 或解析后的目标路径
            $dir = dirname($path);
            if (! is_dir($dir)) {
                if (! mkdir($dir, 0755, true) && ! is_dir($dir)) {
                    $this->error('Failed to create directory: ' . $dir);
                    return Command::FAILURE;
                }
            }

            file_put_contents($path, $xml);

            $this->info("Feed 文件已生成: {$path}");
            $this->info("文件大小: " . number_format(filesize($path) / 1024, 2) . " KB");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("生成 Feed 时出错: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    protected function resolveOutputPath(string $output): string
    {
        if (str_starts_with($output, 'public/')) {
            return public_path(substr($output, 7));
        }

        if (str_starts_with($output, 'storage/')) {
            return storage_path(substr($output, 8));
        }

        if ($output !== '' && $output[0] === DIRECTORY_SEPARATOR) {
            return $output;
        }

        return base_path($output);
    }

    /**
     * 生成 XML Feed
     *
     * @param \Illuminate\Support\Collection $products
     * @param string $baseUrl
     * @param string $channelCode
     * @param string $locale
     * @return string
     */
    protected function generateXML($products, $baseUrl, $channelCode, $locale)
    {
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        // 创建根元素
        $rss = $xml->createElement('rss');
        $rss->setAttribute('version', '2.0');
        $rss->setAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
        $xml->appendChild($rss);

        $channel = $xml->createElement('channel');
        $rss->appendChild($channel);

        // 添加频道信息
        $title = $xml->createElement('title', htmlspecialchars(config('app.name', 'Kiaoa Store')));
        $channel->appendChild($title);

        $link = $xml->createElement('link', htmlspecialchars($baseUrl));
        $channel->appendChild($link);

        $description = $xml->createElement('description', htmlspecialchars('Product Feed for Google Merchant Center'));
        $channel->appendChild($description);

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        // 处理每个产品
        $successCount = 0;
        foreach ($products as $productFlat) {
            try {
                // 确保产品关系已加载
                if (!$productFlat->relationLoaded('product')) {
                    $productFlat->load('product.variants', 'product.super_attributes', 'product.inventories', 'product.categories', 'product.images', 'product.parent.images');
                }
                
                // 确保 product 的 images 关系已加载
                if ($productFlat->product && !$productFlat->product->relationLoaded('images')) {
                    $productFlat->product->load('images', 'parent.images');
                }
                
                $item = $this->createProductItem($xml, $productFlat, $baseUrl, $channel);
                if ($item) {
                    $channel->appendChild($item);
                    $successCount++;
                }
            } catch (\Exception $e) {
                $this->warn("跳过产品 {$productFlat->sku}: " . $e->getMessage());
                if ($this->option('verbose')) {
                    $this->error($e->getTraceAsString());
                }
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("成功生成 {$successCount} 个产品");
        
        return $xml->saveXML();
    }

    /**
     * 创建产品项
     *
     * @param \DOMDocument $xml
     * @param \Webkul\Product\Models\ProductFlat $productFlat
     * @param string $baseUrl
     * @param \DOMElement $channel
     * @return \DOMElement|null
     */
    protected function createProductItem($xml, $productFlat, $baseUrl, $channel)
    {
        $product = $productFlat->product;
        
        if (!$product) {
            return null;
        }

        $item = $xml->createElement('item');
        
        // ID (SKU)
        $this->addChild($xml, $item, 'g:id', $productFlat->sku);

        // Title
        if ($productFlat->name) {
            $this->addChild($xml, $item, 'title', $this->cleanText($productFlat->name));
        } else {
            return null; // 标题是必填项
        }

        // Description
        $description = $productFlat->description ?? $productFlat->short_description ?? '';
        if ($description) {
            $this->addChild($xml, $item, 'description', $this->cleanText($description));
        } else {
            $this->addChild($xml, $item, 'description', $this->cleanText($productFlat->name));
        }

        // Link
        if ($productFlat->url_key) {
            $productUrl = rtrim($baseUrl, '/') . '/' . $productFlat->url_key;
            $this->addChild($xml, $item, 'link', $productUrl);
        } else {
            return null; // 链接是必填项
        }

        // Image Link
        // 确保使用 Product 对象而不是 ProductFlat
        try {
            $baseImage = $this->productImage->getProductBaseImage($product);
            if ($baseImage && isset($baseImage['large_image_url'])) {
                $imageUrl = $baseImage['large_image_url'];
                // 确保是完整URL
                if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $imageUrl = rtrim($baseUrl, '/') . '/' . ltrim($imageUrl, '/');
                }
                $imageUrl = $this->staticMediaUrl($imageUrl);
                $this->addChild($xml, $item, 'g:image_link', $imageUrl);
            }
        } catch (\Exception $e) {
            // 如果获取图片失败，跳过图片，但产品仍然可以生成
            if ($this->option('verbose')) {
                $this->warn("获取产品 {$productFlat->sku} 的主图时出错: " . $e->getMessage());
            }
        }

        // Price
        $price = $this->getProductPrice($productFlat, $product);
        if ($price) {
            $this->addChild($xml, $item, 'g:price', $price . ' USD');
        } else {
            return null; // 价格是必填项
        }

        // Availability
        $availability = $this->getAvailability($product);
        $this->addChild($xml, $item, 'g:availability', $availability);

        // Condition (默认 new)
        $this->addChild($xml, $item, 'g:condition', 'new');

        $brand = $this->overrideBrand ?? $this->getAttributeValue($product, 'brand') ?? 'Kiaoa';
        $this->addChild($xml, $item, 'g:brand', $this->cleanText($brand));

        $ageGroup = $this->overrideAgeGroup ?? $this->getAttributeValue($product, 'age_group') ?? 'adult';
        $this->addChild($xml, $item, 'g:age_group', $ageGroup);

        $gender = $this->overrideGender ?? $this->getAttributeValue($product, 'gender') ?? 'unisex';
        $this->addChild($xml, $item, 'g:gender', $gender);

        // Weight (默认 999 g，如果有重量属性则使用属性值)
        $weight = "0.".round(intval($price),2).' kg' ;//$this->getProductWeight($productFlat, $product);
        if ($weight) {
            $this->addChild($xml, $item, 'g:shipping_weight', $weight);
        } else {
            // 默认值 999 g
            $this->addChild($xml, $item, 'g:shipping_weight', '1 kg');
        }

        $googleProductCategory = $this->overrideGoogleProductCategory 
            ?? $this->getAttributeValue($product, 'google_product_category') 
            ?? 'Sporting Goods > Outdoor Recreation > Fishing';
        $this->addChild($xml, $item, 'g:google_product_category', $googleProductCategory);

        // GTIN (如果有)
        $gtin = $this->getAttributeValue($product, 'gtin') 
            ?? $this->getAttributeValue($product, 'ean')
            ?? $this->getAttributeValue($product, 'upc');
        if ($gtin) {
            $this->addChild($xml, $item, 'g:gtin', $gtin);
        }

        // MPN (如果有)
        $mpn = $this->getAttributeValue($product, 'mpn');
        if ($mpn) {
            $this->addChild($xml, $item, 'g:mpn', $mpn);
        }

        $productType = $this->overrideProductType ?? $this->getProductType($product);
        if ($productType) {
            $this->addChild($xml, $item, 'g:product_type', $this->cleanText($productType));
        }

        // Additional Images
        // 确保使用 Product 对象而不是 ProductFlat
        if ($product && $product->relationLoaded('images')) {
            try {
                $galleryImages = $this->productImage->getGalleryImages($product);
                if (is_array($galleryImages) && count($galleryImages) > 1) {
                    foreach (array_slice($galleryImages, 1, 9) as $image) { // Google最多支持10张图片
                        if (is_array($image) && isset($image['large_image_url'])) {
                            $imageUrl = $image['large_image_url'];
                            if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                                $imageUrl = rtrim($baseUrl, '/') . '/' . ltrim($imageUrl, '/');
                            }
                            $imageUrl = $this->staticMediaUrl($imageUrl);
                            $this->addChild($xml, $item, 'g:additional_image_link', $imageUrl);
                        }
                    }
                }
            } catch (\Exception $e) {
                // 如果获取图片失败，跳过额外图片，不影响主流程
                if ($this->option('verbose')) {
                    $this->warn("获取产品 {$productFlat->sku} 的图片时出错: " . $e->getMessage());
                }
            }
        }

        if ($product->type === 'configurable') {
            $this->addChild($xml, $item, 'g:item_group_id', $productFlat->sku);
        }

        // 处理变体产品（configurable products）
        if ($product->type === 'configurable' && $product->variants && $product->variants->count() > 0) {
            $variants = $product->variants;
            foreach ($variants as $variant) {
                // 获取变体的 ProductFlat
                $variantFlat = $this->productFlatRepository
                    ->where('product_id', $variant->id)
                    ->where('channel', $productFlat->channel)
                    ->where('locale', $productFlat->locale)
                    ->where('status', 1)
                    ->first();
                
                if ($variantFlat && $variantFlat->product) {
                    // 确保加载父产品和super_attributes
                    $variantProduct = $variantFlat->product;
                    if (!$variantProduct->relationLoaded('parent')) {
                        $variantProduct->load('parent.super_attributes');
                    }
                    
                    $variantItem = $this->createVariantItem($xml, $variantFlat, $productFlat, $baseUrl);
                    if ($variantItem) {
                        // 将变体项添加到 channel 元素，而不是 item 的 parentNode
                        // 因为 item 还没有被添加到 DOM 树中
                        $channel->appendChild($variantItem);
                    }
                }
            }
        }

        return $item;
    }

    /**
     * 获取变体 URL 所需的查询参数
     *
     * @param \Webkul\Product\Models\Product $variantProduct
     * @param \Webkul\Product\Models\Product|null $parentProduct
     * @return array
     */
    protected function getVariantQueryParameters($variantProduct, $parentProduct = null)
    {
        $params = [];

        if (! $variantProduct) {
            return $params;
        }

        if (! $parentProduct) {
            $parentProduct = $variantProduct->parent;
        }

        if (! $parentProduct) {
            return $params;
        }

        if (! $parentProduct->relationLoaded('super_attributes')) {
            $parentProduct->load('super_attributes');
        }

        $superAttributes = $parentProduct->super_attributes ?? collect();

        foreach ($superAttributes as $attribute) {
            $code = $attribute->code;

            if (! $code) {
                continue;
            }

            $optionValue = $variantProduct->{$code} ?? null;

            if ($optionValue === null || $optionValue === '') {
                continue;
            }

            if (is_bool($optionValue)) {
                continue;
            }

            if (is_scalar($optionValue)) {
                $params[$code] = (string) $optionValue;
            }
        }

        return $params;
    }

    /**
     * 创建变体产品项
     *
     * @param \DOMDocument $xml
     * @param \Webkul\Product\Models\ProductFlat $variant
     * @param \Webkul\Product\Models\ProductFlat $parent
     * @param string $baseUrl
     * @return \DOMElement|null
     */
    protected function createVariantItem($xml, $variant, $parent, $baseUrl)
    {
        $product = $variant->product;
        
        if (!$product) {
            return null;
        }

        $item = $xml->createElement('item');
        
        // ID (SKU)
        $this->addChild($xml, $item, 'g:id', $variant->sku);

        // Title (父产品名称 + 变体属性)
        $title = $parent->name;
        $variantAttributes = $this->getVariantAttributes($product);
        if ($variantAttributes) {
            $title .= ' - ' . $variantAttributes;
        }
        $this->addChild($xml, $item, 'title', $this->cleanText($title));

        // Description
        // 优先使用变体的描述，如果为空或看起来不像有效描述，则使用父产品的描述
        $description = $variant->description ?? '';
        
        // 如果变体描述看起来像是自动生成的标识符（包含 "variant-" 或类似格式），则忽略它
        if ($description && (preg_match('/variant-\d+-\d+-\d+/', $description) || preg_match('/^\d+-variant-/', $description))) {
            $description = '';
        }
        
        // 如果变体描述为空或无效，使用父产品的描述
        if (empty($description) || strlen(trim($description)) < 10) {
            $description = $parent->description ?? $parent->short_description ?? '';
        }
        
        // 如果仍然为空，使用标题作为描述
        if (empty($description)) {
            $description = $title;
        }
        
        $this->addChild($xml, $item, 'description', $this->cleanText($description));

        // Link (使用父产品的URL)
        if ($parent->url_key) {
            $productUrl = rtrim($baseUrl, '/') . '/' . $parent->url_key;

            $queryParams = $this->getVariantQueryParameters($product, $parent->product ?: $product->parent);

            if (! empty($queryParams)) {
                $productUrl .= (str_contains($productUrl, '?') ? '&' : '?') . http_build_query($queryParams);
            }

            $this->addChild($xml, $item, 'link', $productUrl);
        } else {
            return null;
        }

        // Image Link
        // 确保使用 Product 对象而不是 ProductFlat
        try {
            $baseImage = $this->productImage->getProductBaseImage($product);
            if ($baseImage && isset($baseImage['large_image_url'])) {
                $imageUrl = $baseImage['large_image_url'];
                if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $imageUrl = rtrim($baseUrl, '/') . '/' . ltrim($imageUrl, '/');
                }
                $imageUrl = $this->staticMediaUrl($imageUrl);
                $this->addChild($xml, $item, 'g:image_link', $imageUrl);
            } else {
                // 如果没有变体图片，使用父产品图片
                if ($parent->product) {
                    $parentImage = $this->productImage->getProductBaseImage($parent->product);
                    if ($parentImage && isset($parentImage['large_image_url'])) {
                        $imageUrl = $parentImage['large_image_url'];
                        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                            $imageUrl = rtrim($baseUrl, '/') . '/' . ltrim($imageUrl, '/');
                        }
                        $imageUrl = $this->staticMediaUrl($imageUrl);
                        $this->addChild($xml, $item, 'g:image_link', $imageUrl);
                    }
                }
            }
        } catch (\Exception $e) {
            // 如果获取图片失败，跳过图片
            if ($this->option('verbose')) {
                $this->warn("获取变体 {$variant->sku} 的图片时出错: " . $e->getMessage());
            }
        }

        // Price
        $price = $this->getProductPrice($variant, $product);
        if ($price) {
            $this->addChild($xml, $item, 'g:price', $price . ' USD');
        } else {
            return null;
        }

        // Availability
        $availability = $this->getAvailability($product);
        $this->addChild($xml, $item, 'g:availability', $availability);

        // Condition
        $this->addChild($xml, $item, 'g:condition', 'new');

        // Item Group ID (用于标识变体组)
        $this->addChild($xml, $item, 'g:item_group_id', $parent->sku);

        // Variant Attributes
        $variantAttrs = $this->getVariantAttributesForGoogle($product);
        foreach ($variantAttrs as $name => $value) {
            $this->addChild($xml, $item, 'g:' . $name, $this->cleanText($value));
        }

        $brand = $this->overrideBrand 
            ?? $this->getAttributeValue($product, 'brand') 
            ?? $this->getAttributeValue($parent->product, 'brand') 
            ?? 'Kiaoa';
        $this->addChild($xml, $item, 'g:brand', $this->cleanText($brand));

        $ageGroup = $this->overrideAgeGroup 
            ?? $this->getAttributeValue($product, 'age_group') 
            ?? $this->getAttributeValue($parent->product, 'age_group') 
            ?? 'adult';
        $this->addChild($xml, $item, 'g:age_group', $ageGroup);

        $gender = $this->overrideGender 
            ?? $this->getAttributeValue($product, 'gender') 
            ?? $this->getAttributeValue($parent->product, 'gender') 
            ?? 'unisex';
        $this->addChild($xml, $item, 'g:gender', $gender);

        // Weight (默认 999 g，如果有重量属性则使用属性值)
        //$weight = $this->getProductWeight($variant, $product);
        $weight = "0.".round(intval($price),2).' kg' ;
        if ($weight) {
            $this->addChild($xml, $item, 'g:shipping_weight', $weight);
        } else {
            // 默认值 999 g
            $this->addChild($xml, $item, 'g:shipping_weight', '999 g');
        }

        $googleProductCategory = $this->overrideGoogleProductCategory 
            ?? $this->getAttributeValue($product, 'google_product_category') 
            ?? $this->getAttributeValue($parent->product, 'google_product_category') 
            ?? 'Sporting Goods > Outdoor Recreation > Fishing';
        $this->addChild($xml, $item, 'g:google_product_category', $googleProductCategory);

        // GTIN
        $gtin = $this->getAttributeValue($product, 'gtin') 
            ?? $this->getAttributeValue($product, 'ean')
            ?? $this->getAttributeValue($product, 'upc');
        if ($gtin) {
            $this->addChild($xml, $item, 'g:gtin', $gtin);
        }

        // MPN
        $mpn = $this->getAttributeValue($product, 'mpn');
        if ($mpn) {
            $this->addChild($xml, $item, 'g:mpn', $mpn);
        }

        return $item;
    }

    /**
     * 添加子元素
     *
     * @param \DOMDocument $xml
     * @param \DOMElement $parent
     * @param string $name
     * @param string $value
     * @return void
     */
    protected function addChild($xml, $parent, $name, $value)
    {
        if ($value !== null && $value !== '') {
            $child = $xml->createElement($name, htmlspecialchars($value, ENT_XML1, 'UTF-8'));
            $parent->appendChild($child);
        }
    }

    /**
     * 清理文本内容
     *
     * @param string $text
     * @return string
     */
    protected function cleanText($text)
    {
        // 移除HTML标签
        $text = strip_tags($text);
        // 移除多余的空白字符
        $text = preg_replace('/\s+/', ' ', $text);
        // 截断到5000字符（Google限制）
        return mb_substr(trim($text), 0, 5000);
    }

    /**
     * 获取产品价格
     *
     * @param \Webkul\Product\Models\ProductFlat $productFlat
     * @param \Webkul\Product\Models\Product $product
     * @return string|null
     */
    protected function getProductPrice($productFlat, $product)
    {
        $typeInstance = $product->getTypeInstance();
        $price = $typeInstance->getMinimalPrice();
        
        if ($price) {
            // Google要求格式: 数字 + 货币代码，例如: 29.99 USD
            //$currencyCode = core()->getChannelBaseCurrencyCode() ?? 'USD';
            //return number_format($price, 2, '.', '') . ' ' . $currencyCode;
            return number_format($price, 2, '.', '');
        }
        
        return null;
    }

    /**
     * 获取库存状态
     *
     * @param \Webkul\Product\Models\Product $product
     * @return string
     */
    protected function getAvailability($product)
    {
        return 'in stock';
        
        $typeInstance = $product->getTypeInstance();
        
        if ($typeInstance->isSaleable()) {
            // 检查库存
            $qty = 0;
            $inventories = $product->inventories ?? collect();
            if ($inventories) {
                foreach ($inventories as $inventory) {
                    $qty += $inventory->qty ?? 0;
                }
            }
            
            return $qty > 0 ? 'in stock' : 'out of stock';
        }
        
        // 默认返回 in stock
        return 'in stock';
    }

    /**
     * 获取产品重量
     *
     * @param \Webkul\Product\Models\ProductFlat $productFlat
     * @param \Webkul\Product\Models\Product $product
     * @return string|null 格式: "999 g" 或 "1.5 kg"
     */
    protected function getProductWeight($productFlat, $product)
    {
        // 优先从 ProductFlat 获取重量
        if ($productFlat->weight && $productFlat->weight > 0) {
            // 假设重量单位是 kg，转换为 g
            $weightInGrams = $productFlat->weight * 1000;
            return number_format($weightInGrams, 0, '.', '') . ' g';
        }
        
        // 尝试从产品属性获取重量
        $weight = $this->getAttributeValue($product, 'weight');
        if ($weight && $weight > 0) {
            // 假设重量单位是 kg，转换为 g
            $weightInGrams = $weight * 1000;
            return number_format($weightInGrams, 0, '.', '') . ' g';
        }
        
        return null;
    }

    /**
     * 获取属性值
     *
     * @param \Webkul\Product\Models\Product $product
     * @param string $attributeCode
     * @return string|null
     */
    protected function getAttributeValue($product, $attributeCode)
    {
        try {
            $attributeValue = $product->attribute_values()
                ->whereHas('attribute', function($query) use ($attributeCode) {
                    $query->where('code', $attributeCode);
                })
                ->with('attribute')
                ->first();
            
            if ($attributeValue && $attributeValue->attribute) {
                $attribute = $attributeValue->attribute;
                $value = null;
                
                // 根据属性类型获取值
                if ($attribute->type === 'select') {
                    // 对于 select 类型，integer_value 存储的是 option ID，需要获取 option 的 label
                    $optionId = $attributeValue->integer_value;
                    if ($optionId) {
                        $option = $this->attributeOptionRepository->find($optionId);
                        if ($option) {
                            $value = $option->label ?? $option->admin_name ?? null;
                        }
                    }
                } elseif ($attribute->type === 'multiselect' || $attribute->type === 'checkbox') {
                    // 对于 multiselect 和 checkbox，text_value 存储的是逗号分隔的 option IDs
                    $optionIds = $attributeValue->text_value;
                    if ($optionIds) {
                        $labels = [];
                        $options = $this->attributeOptionRepository->findWhereIn('id', explode(',', $optionIds));
                        foreach ($options as $option) {
                            if ($label = $option->label ?? $option->admin_name) {
                                $labels[] = $label;
                            }
                        }
                        $value = implode(', ', $labels);
                    }
                } else {
                    // 其他类型直接获取值
                    $value = $attributeValue->text_value ?? $attributeValue->boolean_value ?? $attributeValue->integer_value ?? $attributeValue->float_value ?? null;
                }
                
                return $value;
            }
        } catch (\Exception $e) {
            // 忽略错误
        }
        
        return null;
    }

    /**
     * 获取产品类型（分类路径）
     *
     * @param \Webkul\Product\Models\Product $product
     * @return string|null
     */
    protected function getProductType($product)
    {
        try {
            $categories = $product->categories;
            if ($categories && $categories->count() > 0) {
                // 获取第一个分类的完整路径
                $category = $categories->first();
                $path = [];
                $current = $category;
                
                while ($current) {
                    array_unshift($path, $current->name);
                    $current = $current->parent;
                }
                
                return implode(' > ', $path);
            }
        } catch (\Exception $e) {
            // 忽略错误
        }
        
        return null;
    }

    /**
     * 获取变体属性字符串
     *
     * @param \Webkul\Product\Models\Product $product
     * @return string
     */
    protected function getVariantAttributes($product)
    {
        $attrs = [];
        
        try {
            if ($product->parent_id && $product->parent) {
                $superAttributes = $product->parent->super_attributes ?? collect();
                
                if ($superAttributes && (is_array($superAttributes) || is_object($superAttributes))) {
                    foreach ($superAttributes as $superAttribute) {
                        $value = $this->getAttributeValue($product, $superAttribute->code);
                        if ($value) {
                            $attrs[] = $superAttribute->name . ': ' . $value;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // 忽略错误
        }
        
        return implode(', ', $attrs);
    }

    /**
     * 获取变体属性用于Google Feed
     *
     * @param \Webkul\Product\Models\Product $product
     * @return array
     */
    protected function getVariantAttributesForGoogle($product)
    {
        $attrs = [];
        
        try {
            if ($product->parent_id && $product->parent) {
                $superAttributes = $product->parent->super_attributes ?? collect();
                
                if ($superAttributes && (is_array($superAttributes) || is_object($superAttributes))) {
                    foreach ($superAttributes as $superAttribute) {
                        $value = $this->getAttributeValue($product, $superAttribute->code);
                        if ($value) {
                            // 将属性代码映射到Google标准属性
                            $googleAttr = $this->mapToGoogleAttribute($superAttribute->code);
                            if ($googleAttr) {
                                $attrs[$googleAttr] = $value;
                            } else {
                                // 使用自定义属性
                                $attrs['custom_label_0'] = $superAttribute->name . ': ' . $value;
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // 忽略错误
        }
        
        return $attrs;
    }

    /**
     * 映射到Google标准属性
     *
     * @param string $attributeCode
     * @return string|null
     */
    protected function mapToGoogleAttribute($attributeCode)
    {
        $mapping = [
            'color' => 'color',
            'size' => 'size',
            'material' => 'material',
            'pattern' => 'pattern',
            'gender' => 'gender',
            'age_group' => 'age_group',
        ];
        
        return $mapping[strtolower($attributeCode)] ?? null;
    }

    protected function staticMediaUrl($url){
        if(stripos($url,'cache/large') !== false){
            $url = str_replace('cache/large','storage',$url);
        }
        return $url;
    }
}
