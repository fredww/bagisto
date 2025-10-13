<?php

// 简单的验证脚本
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 获取配置产品
$product = \Webkul\Product\Models\Product::where('type', 'configurable')->latest()->first();

if (!$product) {
    echo "No configurable products found.\n";
    exit;
}

echo "=== Product Import Verification ===\n";
echo "Product ID: " . $product->id . "\n";
echo "Product Name: " . ($product->name ?: 'No Name') . "\n";
echo "Product Type: " . $product->type . "\n";
echo "SKU: " . $product->sku . "\n";
echo "Status: " . $product->status . "\n";

// 检查变体
$variants = $product->variants;
echo "\n=== Variants ===\n";
echo "Variants Count: " . $variants->count() . "\n";
foreach ($variants as $variant) {
    echo "  - ID: " . $variant->id . ", SKU: " . $variant->sku . ", Name: " . ($variant->name ?: 'No Name') . "\n";
}

// 检查超级属性
echo "\n=== Super Attributes ===\n";
$superAttributes = $product->super_attributes;
echo "Super Attributes Count: " . $superAttributes->count() . "\n";
foreach ($superAttributes as $attr) {
    echo "  - " . $attr->name . " (Code: " . $attr->code . ")\n";
}

// 检查分类
echo "\n=== Categories ===\n";
$categories = $product->categories;
echo "Categories Count: " . $categories->count() . "\n";
foreach ($categories as $category) {
    echo "  - ID: " . $category->id . ", Name: " . $category->name . "\n";
}

// 检查图片
echo "\n=== Images ===\n";
$images = $product->images;
echo "Images Count: " . $images->count() . "\n";
foreach ($images as $image) {
    echo "  - Path: " . $image->path . "\n";
}

// 检查存储中的图片文件
echo "\n=== Storage Files ===\n";
$storagePath = storage_path('app/public/product/' . $product->id);
echo "Storage Path: " . $storagePath . "\n";
if (file_exists($storagePath)) {
    $files = scandir($storagePath);
    $imageFiles = array_filter($files, function($file) {
        return !in_array($file, ['.', '..']);
    });
    echo "Files in storage: " . count($imageFiles) . " files found\n";
    foreach ($imageFiles as $file) {
        echo "  - " . $file . "\n";
    }
} else {
    echo "No image directory found in storage\n";
}

// 检查产品的其他属性
echo "\n=== Product Attributes ===\n";
$productFlat = $product->product_flats()->first();
if ($productFlat) {
    echo "Description: " . substr(strip_tags($productFlat->description ?? ''), 0, 100) . "...\n";
    echo "Short Description: " . ($productFlat->short_description ?? 'N/A') . "\n";
    echo "Meta Title: " . ($productFlat->meta_title ?? 'N/A') . "\n";
    echo "Meta Keywords: " . ($productFlat->meta_keywords ?? 'N/A') . "\n";
    echo "URL Key: " . ($productFlat->url_key ?? 'N/A') . "\n";
} else {
    echo "No product flat data found\n";
}

echo "\n=== Import Verification Completed ===\n";