<?php
/**
 * 清理所有Shopify导入的产品
 * 使用方法: php clean_shopify_products.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== 清理所有Shopify导入的产品 ===\n\n";

// 查找所有shopify产品
//$allProducts = DB::table('products')->where('sku', 'like', 'shopify-%')->get();
$allProducts = DB::table('products')->get();
$count = $allProducts->count();

if ($count === 0) {
    echo "✅ 没有找到需要删除的Shopify产品\n";
    exit(0);
}

echo "找到 {$count} 个Shopify产品\n";

// 统计
$configurableCount = $allProducts->where('type', 'configurable')->count();
$variantCount = $allProducts->whereNotNull('parent_id')->count();

echo "  - {$configurableCount} 个可配置产品\n";
echo "  - {$variantCount} 个变体\n";

// 确认删除
echo "\n是否确认删除这些产品？这将删除所有关联数据（图片、属性值、库存等）\n";
echo "输入 'yes' 继续，或按Enter键取消: ";
$handle = fopen ("php://stdin","r");
$line = fgets($handle);
if(trim($line) != 'yes'){
    echo "已取消\n";
    exit(0);
}

$allProductIds = $allProducts->pluck('id');
$configurableIds = $allProducts->where('type', 'configurable')->pluck('id');

DB::beginTransaction();
try {
    // 删除产品图片
    $imageCount = DB::table('product_images')->whereIn('product_id', $allProductIds)->delete();
    echo "\n✓ 删除了 {$imageCount} 个产品图片记录\n";
    
    // 删除产品属性值
    $attrValueCount = DB::table('product_attribute_values')->whereIn('product_id', $allProductIds)->delete();
    echo "✓ 删除了 {$attrValueCount} 个属性值\n";
    
    // 删除产品库存
    $inventoryCount = DB::table('product_inventories')->whereIn('product_id', $allProductIds)->delete();
    echo "✓ 删除了 {$inventoryCount} 个库存记录\n";
    
    // 删除产品分类关联
    $categoryCount = DB::table('product_categories')->whereIn('product_id', $allProductIds)->delete();
    echo "✓ 删除了 {$categoryCount} 个分类关联\n";
    
    // 删除产品渠道关联
    $channelCount = DB::table('product_channels')->whereIn('product_id', $allProductIds)->delete();
    echo "✓ 删除了 {$channelCount} 个渠道关联\n";
    
    // 删除超级属性关联（可配置产品）
    if ($configurableIds->count() > 0) {
        $superAttrCount = DB::table('product_super_attributes')->whereIn('product_id', $configurableIds)->delete();
        echo "✓ 删除了 {$superAttrCount} 个超级属性关联\n";
    }
    
    // 删除产品flat表数据（如果存在）
    if (DB::getSchemaBuilder()->hasTable('product_flat')) {
        $flatCount = DB::table('product_flat')->whereIn('product_id', $allProductIds)->delete();
        echo "✓ 删除了 {$flatCount} 个产品flat记录\n";
    }
    
    // 最后删除产品本身
    $productCount = DB::table('products')->whereIn('id', $allProductIds)->delete();
    echo "✓ 删除了 {$productCount} 个产品\n";
    
    DB::commit();
    
    echo "\n✅ 所有Shopify产品已彻底删除！\n\n";
    
    // 验证
    $remaining = DB::table('products')->where('sku', 'like', 'shopify-%')->count();
    echo "验证：数据库中剩余 {$remaining} 个Shopify产品\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "\n❌ 删除失败: " . $e->getMessage() . "\n";
    exit(1);
}

