# Shopify到Bagisto产品导入脚本修复说明

## 修复概述

本次修复主要解决了从Shopify导入产品到Bagisto时的多个关键问题，确保Simple和Configurable产品能够正确导入，包括所有属性、变体、图片和分类。

## 主要修复内容

### 1. Simple产品创建方法 (createSimpleProduct)

**问题**：
- 原先使用REST API的两步创建方式（POST创建基础结构，PUT更新详情）
- 图片处理方法未定义

**修复**：
- 改用ProductRepository直接创建产品
- 使用正确的数据格式，包括所有必需字段
- 添加channel和locale信息
- 实现了完整的handleProductImages方法

**关键改进**：
```php
// 使用Repository创建产品
$product = $this->productRepository->create($productData);

// 更新产品详细信息（包括属性值）
$this->productRepository->update($productData, $product->id);

// 处理产品图片
$this->handleProductImages($product, $shopifyProduct['images']);
```

### 2. Configurable产品创建方法 (createConfigurableProduct)

**问题**：
- 数据格式不正确，使用了嵌套的语言数组（en/zh_CN）
- 属性创建缺少attributeFamily参数
- 缺少错误堆栈跟踪

**修复**：
- 修正数据结构，使用扁平化的属性结构
- 添加channel和locale字段
- 改进错误处理，添加堆栈跟踪
- 修复属性创建方法调用，传递attributeFamily参数

**关键改进**：
```php
$baseProductData = [
    'type' => 'configurable',
    'attribute_family_id' => $attributeFamily->id,
    'sku' => $sku,
    'super_attributes' => $superAttributes,
    'name' => $shopifyProduct['title'],  // 扁平化结构
    'url_key' => Str::slug($shopifyProduct['title']) . '-' . time(),
    // ... 其他属性
    'channel' => $channel->code,
    'locale' => 'en',
];
```

### 3. 变体更新方法 (updateVariantsWithShopifyData)

**问题**：
- 使用了嵌套的语言数组结构
- 数据类型未正确转换

**修复**：
- 改用扁平化的数据结构
- 添加类型转换（float/int）
- 确保SKU正确更新
- 添加channel和locale字段

**关键改进**：
```php
$updateData = [
    'name' => $shopifyVariant['title'] ?? $configurableProduct->name,
    'url_key' => Str::slug($shopifyVariant['title'] ?? $configurableProduct->name) . '-' . $matchingVariant->id,
    'price' => (float) ($shopifyVariant['price'] ?? 0),
    'weight' => (float) ($shopifyVariant['weight'] ?? 1),
    'sku' => $shopifyVariant['sku'] ?? $matchingVariant->sku,
    'channel' => $channel->code,
    'locale' => 'en',
];
```

### 4. 产品图片处理 (handleProductImages)

**问题**：
- processProductImages方法未实现完整的数据库记录创建
- handleProductImages方法未定义

**修复**：
- 完全实现handleProductImages方法
- 使用ProductImageRepository创建图片记录
- 正确保存图片到Storage
- 添加错误处理和日志

**关键改进**：
```php
// 下载图片并保存到Storage
Storage::put($path, $imageContent);

// 创建产品图片记录
$productImageRepository->create([
    'product_id' => $product->id,
    'type' => 'images',
    'path' => $path,
    'position' => $index + 1
]);
```

### 5. 属性创建方法 (createOrGetAttribute)

**问题**：
- 缺少attributeFamily参数
- 属性代码未规范化
- 未处理属性与属性族的关联

**修复**：
- 添加attributeFamily参数
- 使用Str::slug规范化属性代码
- 自动将属性关联到属性族
- 添加更多属性字段（value_per_locale, value_per_channel等）

**关键改进**：
```php
// 规范化属性代码
$attributeCode = Str::slug(strtolower($optionName), '_');

// 创建属性后，自动关联到属性族
DB::table('attribute_family_attributes')->insert([
    'attribute_family_id' => $attributeFamily->id,
    'attribute_id' => $attribute->id,
]);
```

### 6. 属性选项创建方法 (getOrCreateAttributeOption)

**问题**：
- 多语言标签创建不完整

**修复**：
- 刷新属性以获取最新选项
- 正确创建多语言标签记录
- 改进日志输出

**关键改进**：
```php
// 创建属性选项
$option = $this->attributeOptionRepository->create($optionData);

// 创建多语言标签
DB::table('attribute_option_translations')->insert([
    [
        'attribute_option_id' => $option->id,
        'locale' => 'en',
        'label' => $optionValue,
    ],
    [
        'attribute_option_id' => $option->id,
        'locale' => 'zh_CN',
        'label' => $optionValue,
    ],
]);
```

### 7. 删除未使用的代码

**删除**：
- getAdminToken方法（不再使用REST API认证）
- processProductImages方法（已被handleProductImages替代）
- adminToken属性缓存（不再需要）

## 使用方法

```bash
# 导入单个集合的1个产品（测试）
php artisan shopify:import-products https://kiaoa.com --collection=spinning-reel --limit=1

# 导入单个集合的多个产品
php artisan shopify:import-products https://kiaoa.com --collection=spinning-reel --limit=50

# 导入多个集合
php artisan shopify:import-products https://kiaoa.com --collection=spinning-reel --collection=fishing-rod --limit=10

# 模拟运行（不实际导入）
php artisan shopify:import-products https://kiaoa.com --collection=spinning-reel --limit=5 --dry-run
```

## 数据流程

1. **获取Shopify产品数据** → 从Shopify API获取产品JSON
2. **SKU生成和验证** → 生成符合Bagisto规则的SKU
3. **分类处理** → 创建或获取对应的产品分类
4. **产品类型判断**：
   - 如果有多个变体 → 创建Configurable产品
   - 否则 → 创建Simple产品
5. **属性处理**（仅Configurable）：
   - 创建或获取属性
   - 创建或获取属性选项
   - 关联属性到属性族
6. **产品创建** → 使用ProductRepository创建产品
7. **变体更新**（仅Configurable）→ 更新自动创建的变体数据
8. **图片处理** → 下载并保存产品图片

## 注意事项

1. **数据库事务**：整个导入过程在事务中进行，出错会自动回滚
2. **SKU规则**：SKU只允许字母、数字和连字符
3. **URL Key唯一性**：添加时间戳确保URL key唯一
4. **图片格式**：支持jpg, jpeg, png, gif, webp
5. **错误处理**：Configurable产品创建失败会自动降级为Simple产品
6. **日志输出**：提供中英文双语日志，便于调试

## 测试建议

1. 先使用--dry-run测试
2. 从单个产品开始（--limit=1）
3. 检查产品是否正确创建：
   - 产品名称和描述
   - 价格和库存
   - 分类关联
   - 图片显示
   - 属性和变体（Configurable产品）
4. 检查后台管理界面的产品显示
5. 检查前台产品页面的显示

## 相关文件

- `/app/Console/Commands/ImportShopifyProducts.php` - 主导入脚本
- `/packages/Webkul/Product/src/Repositories/ProductRepository.php` - 产品Repository
- `/packages/Webkul/Product/src/Type/Simple.php` - Simple产品类型
- `/packages/Webkul/Product/src/Type/Configurable.php` - Configurable产品类型
- `/packages/Webkul/Product/src/Repositories/ProductImageRepository.php` - 图片Repository

## 修复日期

2025年10月12日


