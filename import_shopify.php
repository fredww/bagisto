<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Product\Repositories\ProductImageRepository;
use Webkul\Attribute\Models\Attribute;

require 'vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$productRepository = app(ProductRepository::class);
$attributeRepository = app(AttributeRepository::class);
$channelRepository = app(ChannelRepository::class);
$imageRepository = app(ProductImageRepository::class);

$channel = $channelRepository->findOneWhere(['code' => 'default']);
if (!$channel) {
    die("❌ 没有找到默认渠道 (channel)\n");
}

$importDir = storage_path('app/public/imported');
if (!file_exists($importDir)) mkdir($importDir, 0777, true);

$json = file_get_contents('shopify_products.json');
$shopifyProducts = json_decode($json, true);

foreach ($shopifyProducts['products'] as $sProduct) {
    echo "🛠 正在导入：" . $sProduct['title'] . PHP_EOL;

    $variants = $sProduct['variants'] ?? [];

    // ----------------------------
    // STEP 1: 自动创建属性
    // ----------------------------
    $variantAttributes = [];
    if (isset($sProduct['options'])) {
        foreach ($sProduct['options'] as $option) {
            $attributeCode = strtolower(str_replace(' ', '_', $option['name']));

            $attribute = $attributeRepository->findOneByField('code', $attributeCode);
            if (!$attribute) {
                echo "➕ 创建属性：{$attributeCode}\n";
                $attribute = $attributeRepository->create([
                    'code' => $attributeCode,
                    'admin_name' => ucfirst($option['name']),
                    'type' => 'select',
                    'is_configurable' => 1,
                    'is_user_defined' => 1,
                    'is_visible_on_front' => 1,
                    'swatch_type' => 'dropdown',
                    'options' => [],
                ]);
            }

            $variantAttributes[] = $attributeCode;
        }
    }

    // ----------------------------
    // STEP 2: 判断商品类型
    // ----------------------------
    if (count($variants) <= 1) {
        // ========== SIMPLE ==========
        $variant = $variants[0] ?? [];

        $simpleData = [
            'type' => 'simple',
            'attribute_family_id' => 1,
            'sku' => $variant['sku'] ?: uniqid('sku_'),
            'name' => $sProduct['title'],
            'url_key' => $sProduct['handle'] ?? Str::slug($sProduct['title']),
            'description' => $sProduct['body_html'] ?? '',
            'short_description' => substr(strip_tags($sProduct['body_html'] ?? ''), 0, 180),
            'status' => 1,
            'price' => $variant['price'] ?? 0,
            'channel' => $channel->id,
            'inventories' => [
                'default' => $variant['inventory_quantity'] ?? 0,
            ],
        ];

        $simple = $productRepository->create($simpleData);

        // 导入图片
        importShopifyImages($imageRepository, $simple->id, $sProduct['images'], $importDir);

        echo "✅ 导入 Simple 产品: {$simple->name}\n";
    } else {
        // ========== CONFIGURABLE ==========
        $configurable = $productRepository->create([
            'type' => 'configurable',
            'sku' => $sProduct['handle'] ?? uniqid('cfg_'),
            'attribute_family_id' => 1,
            'name' => $sProduct['title'],
            'url_key' => $sProduct['handle'] ?? Str::slug($sProduct['title']),
            'description' => $sProduct['body_html'] ?? '',
            'short_description' => substr(strip_tags($sProduct['body_html'] ?? ''), 0, 180),
            'status' => 1,
            'price' => $variants[0]['price'] ?? 0,
            'channel' => $channel->id,
        ]);

        $variantIds = [];

        foreach ($variants as $variant) {
            $variantData = [
                'type' => 'simple',
                'attribute_family_id' => 1,
                'sku' => $variant['sku'] ?: uniqid('sku_'),
                'name' => $sProduct['title'] . ' - ' . $variant['title'],
                'price' => $variant['price'] ?? 0,
                'weight' => $variant['weight'] ?? 0,
                'status' => 1,
                'inventories' => ['default' => $variant['inventory_quantity'] ?? 0],
            ];

            foreach ($variantAttributes as $idx => $code) {
                if (!empty($variant['option' . ($idx + 1)])) {
                    $variantData[$code] = $variant['option' . ($idx + 1)];
                }
            }

            $simple = $productRepository->create($variantData);
            $variantIds[] = $simple->id;

            // 匹配变体图片
            $variantImages = array_filter($sProduct['images'], function ($img) use ($variant) {
                return isset($variant['image_id']) && $img['id'] == $variant['image_id'];
            });
            importShopifyImages($imageRepository, $simple->id, $variantImages, $importDir);
        }

        // 关联 configurable 和子变体
        $productRepository->update([
            'super_attributes' => $variantAttributes,
            'variants' => $variantIds,
        ], $configurable->id);

        // 主商品图片
        importShopifyImages($imageRepository, $configurable->id, $sProduct['images'], $importDir);

        echo "✅ 导入 Configurable 产品: {$sProduct['title']} ({count($variantIds)} 个变体)\n";
    }
}

echo "🎉 全部导入完成！\n";

/**
 * 下载并导入 Shopify 图片
 */
function importShopifyImages($imageRepository, $productId, $images, $dir)
{
    foreach ($images as $img) {
        $src = $img['src'] ?? null;
        if (!$src) continue;

        $fileName = basename(parse_url($src, PHP_URL_PATH));
        $localPath = $dir . '/' . $fileName;

        if (!file_exists($localPath)) {
            file_put_contents($localPath, file_get_contents($src));
        }

        $imageRepository->create([
            'path' => 'imported/' . $fileName,
            'product_id' => $productId,
        ]);
    }
}
