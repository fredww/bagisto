# Shopify to Bagisto Product Import Guide

## Overview

This guide explains how to use the Shopify product import command to migrate products from a Shopify store to your Bagisto e-commerce platform.

## Features

- ✅ Import products from any Shopify store
- ✅ Automatic category creation and mapping
- ✅ Product image downloading and processing
- ✅ Support for product variants (basic implementation)
- ✅ Dry-run mode for testing
- ✅ Configurable import limits
- ✅ Error handling and logging

## Installation

The import command is already included in your Bagisto installation. No additional setup is required.

## Usage

### Basic Command Structure

```bash
php artisan shopify:import-products {shopify_url} [options]
```

### Parameters

- `shopify_url`: The base URL of the Shopify store (e.g., `https://example.myshopify.com`)

### Options

- `--collection=COLLECTION`: Specific collection(s) to import (can be used multiple times)
- `--limit=NUMBER`: Number of products to import per collection (default: 50)
- `--dry-run`: Preview what would be imported without actually importing

### Examples

#### Import all products from a store
```bash
php artisan shopify:import-products https://kiaoa.com
```

#### Import specific collections
```bash
php artisan shopify:import-products https://kiaoa.com --collection=spinning-reel --collection=fishing-gear
```

#### Import with limit
```bash
php artisan shopify:import-products https://kiaoa.com --collection=spinning-reel --limit=10
```

#### Dry run (preview only)
```bash
php artisan shopify:import-products https://kiaoa.com --collection=spinning-reel --limit=5 --dry-run
```

## How It Works

### 1. Product Discovery
The command fetches products from Shopify's JSON API endpoints:
- `{store_url}/collections/{collection}/products.json`
- Falls back to alternative URL formats if needed

### 2. Data Transformation
Each Shopify product is transformed to match Bagisto's data structure:
- **SKU**: Prefixed with `shopify_` to avoid conflicts
- **Categories**: Automatically created based on collection names
- **Images**: Downloaded and stored locally
- **Variants**: Detected and handled (currently creates simple products)

### 3. Import Process
- Checks for existing products (by SKU)
- Creates categories if they don't exist
- Downloads and processes product images
- Creates product records in Bagisto

## Product Mapping

| Shopify Field | Bagisto Field | Notes |
|---------------|---------------|-------|
| `title` | `name` | Product name |
| `handle` | `sku` | Prefixed with `shopify_` |
| `body_html` | `description` | Full description |
| `body_html` (truncated) | `short_description` | Limited to 255 chars |
| `tags` | `meta_keywords` | Comma-separated |
| `images` | Product images | Downloaded locally |
| `variants[0].price` | `price` | Uses first variant price |
| `variants[0].weight` | `weight` | Uses first variant weight |
| `variants[0].inventory_quantity` | Inventory | Stock quantity |

## Variant Support

Currently, the import command detects products with multiple variants but creates them as simple products. This is indicated by warning messages during import:

```
检测到产品变体，创建可配置产品: Product Name
Detected product variants, creating configurable product: Product Name
变体支持功能正在开发中，将创建为简单产品
Variant support is under development, creating as simple product
```

## Error Handling

The command includes comprehensive error handling:
- Network timeouts and retries
- Invalid URLs or missing collections
- Database transaction rollbacks on errors
- Detailed error logging

## Troubleshooting

### Common Issues

1. **"Too many arguments" error**
   - Use `--collection=name` instead of positional arguments
   - Correct: `php artisan shopify:import-products https://store.com --collection=products`

2. **404 errors when fetching products**
   - Verify the store URL is correct
   - Check if the collection name exists
   - Some stores may have different URL structures

3. **Image download failures**
   - Check internet connectivity
   - Verify storage permissions for `storage/app/public/product/`

4. **Products not appearing in frontend**
   - Ensure products are assigned to the correct channel
   - Check product status and visibility settings
   - Run `php artisan config:cache` to clear cache

### Debugging

Enable verbose output by adding `-v` flag:
```bash
php artisan shopify:import-products https://store.com --collection=products -v
```

## File Structure

The import command is located at:
```
app/Console/Commands/ImportShopifyProducts.php
```

Key methods:
- `handle()`: Main command execution
- `getShopifyCollections()`: Fetch available collections
- `importCollectionProducts()`: Process products from a collection
- `importSingleProduct()`: Import individual product
- `createSimpleProduct()`: Create simple product in Bagisto
- `createConfigurableProduct()`: Handle variant products (placeholder)
- `handleProductImages()`: Download and process images

## Storage

- **Product images**: Stored in `storage/app/public/product/`
- **Database**: Products stored in standard Bagisto tables (`products`, `product_flat`, etc.)

## Performance Considerations

- Use `--limit` option for large imports to avoid memory issues
- Run imports during off-peak hours
- Monitor disk space for image storage
- Consider running in background for large datasets

## Future Enhancements

- Full configurable product support with variants
- Custom attribute mapping
- Bulk update functionality
- Import scheduling
- Progress bars for large imports
- Webhook support for real-time sync

## Support

For issues or questions:
1. Check the command output for specific error messages
2. Verify Shopify store accessibility
3. Check Bagisto logs in `storage/logs/`
4. Ensure proper file permissions for storage directories