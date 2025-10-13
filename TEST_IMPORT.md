# 导入测试指南

## 测试步骤

### 1. 检查命令是否可用

```bash
php artisan shopify:import-products --help
```

预期输出：显示命令帮助信息

### 2. 模拟运行测试（不实际导入）

```bash
php artisan shopify:import-products https://kiaoa.com --collection=spinning-reel --limit=1 --dry-run
```

预期输出：
- 显示正在处理的集合
- 显示找到的产品数量
- 显示 "DRY RUN: 将导入产品" 信息
- 不会实际创建产品

### 3. 导入单个Simple产品测试

```bash
# 导入一个没有变体的产品
php artisan shopify:import-products https://kiaoa.com --collection=spinning-reel --limit=1
```

验证检查：
1. 产品是否创建成功
2. 产品名称、描述是否正确
3. 价格和库存是否正确
4. 分类是否正确关联
5. 图片是否正确上传和显示

### 4. 导入Configurable产品测试

```bash
# 找一个有变体的产品集合
php artisan shopify:import-products https://kiaoa.com --collection=fishing-rod --limit=1
```

验证检查：
1. 可配置产品是否创建成功
2. 属性是否正确创建
3. 属性选项是否正确创建
4. 变体是否自动创建
5. 变体的价格、库存、SKU是否正确
6. 图片是否正确关联
7. 在前台是否可以选择不同的选项

### 5. 批量导入测试

```bash
# 导入多个产品
php artisan shopify:import-products https://kiaoa.com --collection=spinning-reel --limit=5
```

验证检查：
1. 成功导入的产品数量
2. 是否有错误
3. SKU冲突检测（重复导入时）
4. 性能表现

### 6. 多集合导入测试

```bash
# 同时导入多个集合
php artisan shopify:import-products https://kiaoa.com --collection=spinning-reel --collection=fishing-rod --limit=2
```

验证检查：
1. 两个集合的分类是否都正确创建
2. 产品是否分配到正确的分类
3. 总导入数量是否正确

## 验证清单

### 数据库检查

```sql
-- 检查产品
SELECT id, sku, type, created_at FROM products ORDER BY id DESC LIMIT 10;

-- 检查产品属性值
SELECT p.sku, a.code, pav.* 
FROM product_attribute_values pav
JOIN products p ON p.id = pav.product_id
JOIN attributes a ON a.id = pav.attribute_id
WHERE p.sku LIKE 'shopify-%'
ORDER BY p.id DESC LIMIT 20;

-- 检查产品图片
SELECT p.sku, pi.* 
FROM product_images pi
JOIN products p ON p.id = pi.product_id
WHERE p.sku LIKE 'shopify-%'
ORDER BY p.id DESC;

-- 检查可配置产品的变体
SELECT p.sku as parent_sku, v.sku as variant_sku, v.price, v.id
FROM products p
JOIN products v ON v.parent_id = p.id
WHERE p.type = 'configurable' AND p.sku LIKE 'shopify-%'
ORDER BY p.id DESC;

-- 检查属性
SELECT * FROM attributes WHERE code IN (SELECT DISTINCT code FROM attributes WHERE is_user_defined = 1);

-- 检查属性选项
SELECT a.code, ao.admin_name, aot.label, aot.locale
FROM attribute_options ao
JOIN attributes a ON a.id = ao.attribute_id
LEFT JOIN attribute_option_translations aot ON aot.attribute_option_id = ao.id
WHERE a.is_user_defined = 1
ORDER BY a.id, ao.id;
```

### 后台管理界面检查

1. 登录后台管理
2. 进入 Catalog → Products
3. 查找导入的产品（SKU以shopify-开头）
4. 点击编辑产品，检查：
   - 基本信息是否完整
   - 图片是否显示
   - 库存是否正确
   - 分类是否关联
   - 对于可配置产品：
     - 超级属性是否正确
     - 变体列表是否完整
     - 每个变体的信息是否正确

### 前台显示检查

1. 访问产品分类页面
2. 查看导入的产品是否显示
3. 点击进入产品详情页
4. 检查：
   - 产品信息是否完整
   - 图片是否正确显示
   - 价格是否正确
   - 对于可配置产品：
     - 属性选择器是否显示
     - 选择不同选项时价格是否变化
     - 添加到购物车是否正常

## 常见问题排查

### 1. SKU已存在错误

**错误信息**：`Product already exists: [产品名称]`

**解决方法**：
- 删除已导入的产品
- 或修改SKU生成规则

```bash
# 删除所有Shopify导入的产品
# 注意：这是危险操作，请在测试环境执行
DELETE FROM products WHERE sku LIKE 'shopify-%';
```

### 2. 图片下载失败

**错误信息**：`Failed to download image: [URL]`

**可能原因**：
- 网络问题
- 图片URL无效
- 文件权限问题

**解决方法**：
- 检查网络连接
- 检查storage目录权限
- 手动测试图片URL

### 3. 属性创建失败

**错误信息**：数据库约束错误

**可能原因**：
- 属性代码冲突
- 属性族不存在

**解决方法**：
- 检查attributes表
- 确保使用的属性族ID有效

### 4. 变体未正确创建

**问题**：Configurable产品没有变体

**可能原因**：
- super_attributes格式错误
- 属性选项ID不匹配

**解决方法**：
- 检查日志输出
- 验证variantMapping数组
- 检查数据库中的attribute_values

## 性能优化建议

1. **批量导入**：使用合理的limit值（建议50-100）
2. **网络超时**：已设置为60秒，如果仍然超时可以增加
3. **图片处理**：考虑异步处理大量图片
4. **数据库索引**：确保SKU和url_key有索引

## 日志查看

导入过程中的详细日志会输出到控制台，包括：
- 产品处理进度
- 属性创建信息
- 变体更新状态
- 图片处理结果
- 错误信息和堆栈跟踪

## 回滚操作

如果导入出现问题，可以执行以下SQL回滚（仅测试环境）：

```sql
-- 备份当前数据
CREATE TABLE products_backup AS SELECT * FROM products WHERE sku LIKE 'shopify-%';
CREATE TABLE product_images_backup AS SELECT * FROM product_images WHERE product_id IN (SELECT id FROM products WHERE sku LIKE 'shopify-%');

-- 删除导入的产品（会自动删除关联的图片、属性值等）
DELETE FROM products WHERE sku LIKE 'shopify-%';

-- 删除自动创建的属性（可选）
DELETE FROM attributes WHERE is_user_defined = 1 AND code IN ('size', 'color', 'style', etc.);
```

## 成功标准

导入成功的标准：
- [ ] 产品数量与预期一致
- [ ] 所有产品都有正确的SKU
- [ ] Simple产品的价格、库存正确
- [ ] Configurable产品的属性正确创建
- [ ] 所有变体都正确创建并更新
- [ ] 图片成功下载并关联
- [ ] 分类正确创建和关联
- [ ] 后台管理界面正常显示
- [ ] 前台产品页面正常显示
- [ ] 可以正常添加到购物车

测试完成后，标记TODO为完成。


