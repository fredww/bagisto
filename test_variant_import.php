<?php

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Webkul\Product\Repositories\ProductRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Inventory\Repositories\InventorySourceRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Illuminate\Support\Str;

// 获取必要的仓库
$productRepository = app(ProductRepository::class);
$categoryRepository = app(CategoryRepository::class);
$attributeFamilyRepository = app(AttributeFamilyRepository::class);
$channelRepository = app(ChannelRepository::class);
$inventorySourceRepository = app(InventorySourceRepository::class);
$attributeRepository = app(AttributeRepository::class);
$attributeOptionRepository = app(AttributeOptionRepository::class);

// 获取默认值
$category = $categoryRepository->findOrFail(1);
$attributeFamily = $attributeFamilyRepository->findOrFail(1);
$channel = $channelRepository->findOrFail(1);
$inventorySource = $inventorySourceRepository->findOrFail(1);

// 测试产品数据
$testProduct = json_decode(file_get_contents('test_variant_product.json'), true)['product'];

echo "测试产品: {$testProduct['title']}\n";
echo "Test product: {$testProduct['title']}\n";
echo "选项数量: " . count($testProduct['options']) . "\n";
echo "Options count: " . count($testProduct['options']) . "\n";
echo "变体数量: " . count($testProduct['variants']) . "\n";
echo "Variants count: " . count($testProduct['variants']) . "\n";

// 创建或获取属性的函数
function createOrGetAttribute($attributeRepository, $attributeOptionRepository, $name, $values) {
    $attributeCode = Str::slug($name, '_');
    
    // 检查属性是否已存在
    $existingAttribute = $attributeRepository->findOneByField('code', $attributeCode);
    
    if ($existingAttribute) {
        echo "使用现有属性: {$name} (ID: {$existingAttribute->id})\n";
        echo "Using existing attribute: {$name} (ID: {$existingAttribute->id})\n";
        return $existingAttribute;
    }
    
    // 创建新属性
    $attributeData = [
        'code' => $attributeCode,
        'admin_name' => $name,
        'type' => 'select',
        'is_required' => false,
        'is_unique' => false,
        'validation' => null,
        'value_per_locale' => false,
        'value_per_channel' => false,
        'is_filterable' => true,
        'is_configurable' => true,
        'is_user_defined' => true,
        'is_visible_on_front' => true,
        'position' => 1,
        'options' => []
    ];
    
    // 添加选项
    foreach ($values as $index => $value) {
        $attributeData['options'][] = [
            'admin_name' => $value,
            'sort_order' => $index + 1,
            'isNew' => true,
            'isDelete' => false
        ];
    }
    
    $attribute = $attributeRepository->create($attributeData);
    echo "创建新属性: {$name} (ID: {$attribute->id})\n";
    echo "Created new attribute: {$name} (ID: {$attribute->id})\n";
    
    return $attribute;
}

try {
    // 1. 分析选项和变体
    $options = $testProduct['options'] ?? [];
    $variants = $testProduct['variants'] ?? [];
    
    if (empty($options) || empty($variants) || count($variants) <= 1) {
        echo "产品没有足够的变体，无法创建可配置产品\n";
        echo "Product doesn't have enough variants to create configurable product\n";
        exit(1);
    }
    
    // 2. 创建或获取属性
    $superAttributes = [];
    $allOptionValues = [];
    
    foreach ($options as $option) {
        $optionName = $option['name'];
        $optionValues = $option['values'];
        
        $allOptionValues[$optionName] = $optionValues;
        $attribute = createOrGetAttribute($attributeRepository, $attributeOptionRepository, $optionName, $optionValues);
        $superAttributes[] = $attribute->id;
    }
    
    echo "超级属性 IDs: " . implode(', ', $superAttributes) . "\n";
    echo "Super attribute IDs: " . implode(', ', $superAttributes) . "\n";
    
    // 3. 创建可配置产品
    $sku = 'test-configurable-' . time();
    $baseProductData = [
        'type' => 'configurable',
        'attribute_family_id' => $attributeFamily->id,
        'sku' => $sku,
        'super_attributes' => $superAttributes,
        'family' => $attributeFamily->id,
        'en' => [
            'name' => $testProduct['title'],
            'url_key' => Str::slug($testProduct['title']) . '-' . time(),
            'description' => $testProduct['body_html'] ?? '',
            'short_description' => substr($testProduct['body_html'] ?? '', 0, 160),
            'meta_title' => $testProduct['title'],
            'meta_keywords' => $testProduct['title'],
            'meta_description' => substr($testProduct['body_html'] ?? '', 0, 160),
        ],
        'zh_CN' => [
            'name' => $testProduct['title'],
            'url_key' => Str::slug($testProduct['title']) . '-' . time(),
            'description' => $testProduct['body_html'] ?? '',
            'short_description' => substr($testProduct['body_html'] ?? '', 0, 160),
            'meta_title' => $testProduct['title'],
            'meta_keywords' => $testProduct['title'],
            'meta_description' => substr($testProduct['body_html'] ?? '', 0, 160),
        ],
        'status' => 1,
        'weight' => 1,
        'categories' => [$category->id],
        'channels' => [$channel->id],
        'inventories' => [
            $inventorySource->id => 0
        ],
    ];
    
    echo "创建可配置产品...\n";
    echo "Creating configurable product...\n";
    
    $configurableProduct = $productRepository->create($baseProductData);
    
    if ($configurableProduct) {
        echo "✓ 可配置产品创建成功，ID: {$configurableProduct->id}\n";
        echo "✓ Configurable product created successfully, ID: {$configurableProduct->id}\n";
        
        // 4. 创建变体
        $variantCount = 0;
        foreach ($variants as $variant) {
            try {
                // 构建变体属性
                $variantAttributes = [];
                foreach ($options as $optionIndex => $option) {
                    $optionName = $option['name'];
                    $optionValue = $variant["option" . ($optionIndex + 1)] ?? null;
                    
                    if ($optionValue) {
                        // 找到属性
                        $attribute = null;
                        foreach ($superAttributes as $attrId) {
                            $attr = $attributeRepository->find($attrId);
                            if (Str::slug($attr->admin_name, '_') === Str::slug($optionName, '_')) {
                                $attribute = $attr;
                                break;
                            }
                        }
                        
                        if ($attribute) {
                            // 找到选项值
                            $attributeOption = $attributeOptionRepository->findOneWhere([
                                'attribute_id' => $attribute->id,
                                'admin_name' => $optionValue
                            ]);
                            
                            if ($attributeOption) {
                                $variantAttributes[$attribute->id] = $attributeOption->id;
                            }
                        }
                    }
                }
                
                // 创建变体产品数据
                $variantSku = $variant['sku'] ?? ($sku . '-variant-' . ($variantCount + 1));
                $variantData = [
                    'type' => 'simple',
                    'attribute_family_id' => $attributeFamily->id,
                    'sku' => $variantSku,
                    'parent_id' => $configurableProduct->id,
                    'en' => [
                        'name' => $variant['title'] ?? ($testProduct['title'] . ' - ' . $variant['title']),
                        'url_key' => Str::slug($variantSku),
                        'description' => $testProduct['body_html'] ?? '',
                        'short_description' => substr($testProduct['body_html'] ?? '', 0, 160),
                        'meta_title' => $variant['title'] ?? $testProduct['title'],
                        'meta_keywords' => $variant['title'] ?? $testProduct['title'],
                        'meta_description' => substr($testProduct['body_html'] ?? '', 0, 160),
                    ],
                    'zh_CN' => [
                        'name' => $variant['title'] ?? ($testProduct['title'] . ' - ' . $variant['title']),
                        'url_key' => Str::slug($variantSku),
                        'description' => $testProduct['body_html'] ?? '',
                        'short_description' => substr($testProduct['body_html'] ?? '', 0, 160),
                        'meta_title' => $variant['title'] ?? $testProduct['title'],
                        'meta_keywords' => $variant['title'] ?? $testProduct['title'],
                        'meta_description' => substr($testProduct['body_html'] ?? '', 0, 160),
                    ],
                    'status' => 1,
                    'weight' => $variant['weight'] ?? 1,
                    'price' => $variant['price'] ?? 0,
                    'categories' => [$category->id],
                    'channels' => [$channel->id],
                    'inventories' => [
                        $inventorySource->id => $variant['inventory_quantity'] ?? 0
                    ],
                ];
                
                // 添加变体属性
                foreach ($variantAttributes as $attrId => $optionId) {
                    $variantData[$attrId] = $optionId;
                }
                
                $variantProduct = $productRepository->create($variantData);
                
                if ($variantProduct) {
                    echo "  ✓ 变体创建成功: {$variantProduct->sku} (ID: {$variantProduct->id})\n";
                    echo "  ✓ Variant created successfully: {$variantProduct->sku} (ID: {$variantProduct->id})\n";
                    $variantCount++;
                } else {
                    echo "  ✗ 变体创建失败: {$variantSku}\n";
                    echo "  ✗ Failed to create variant: {$variantSku}\n";
                }
                
            } catch (\Exception $e) {
                echo "  ✗ 变体创建异常: " . $e->getMessage() . "\n";
                echo "  ✗ Variant creation exception: " . $e->getMessage() . "\n";
            }
        }
        
        echo "成功创建 {$variantCount} 个变体\n";
        echo "Successfully created {$variantCount} variants\n";
        
    } else {
        echo "✗ 可配置产品创建失败\n";
        echo "✗ Failed to create configurable product\n";
    }
    
} catch (\Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "堆栈跟踪:\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}