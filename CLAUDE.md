# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **Bagisto e-commerce platform** - an open-source Laravel-based e-commerce framework built on PHP 8.2+ and Laravel 11. The platform uses a modular architecture with custom Webkul packages and supports multi-channel, multi-locale e-commerce operations.

## Development Commands

### Laravel Artisan Commands
```bash
# Standard Laravel commands
php artisan serve                    # Start development server
php artisan migrate                  # Run database migrations
php artisan tinker                   # Interactive shell
php artisan queue:work               # Process queue jobs
php artisan schedule:run             # Run scheduled tasks

# Custom Shopify import command
php artisan shopify:import-products {shopify_url} --collection={name} --limit={number} --dry-run

# Package management
php artisan package:discover         # Discover package services
composer install                    # Install PHP dependencies
npm install                         # Install frontend dependencies
```

### Testing
```bash
# Run all tests
php artisan test

# Run specific test suites
./vendor/bin/phpunit --testsuite="Admin Feature Test"
./vendor/bin/phpunit --testsuite="Core Unit Test"
./vendor/bin/phpunit --testsuite="Shop Feature Test"
./vendor/bin/phpunit --testsuite="DataGrid Unit Test"

# Run individual test files
./vendor/bin/phpunit tests/Feature/ProductTest.php
```

### Frontend Development
```bash
# Development
npm run dev                         # Start Vite dev server
npm run build                       # Build for production

# Laravel Pint (PHP code formatting)
./vendor/bin/pint                   # Format code with Laravel Pint rules
```

### Docker Development (Laravel Sail)
```bash
# Start all services
./vendor/bin/sail up

# Start in background
./vendor/bin/sail up -d

# Execute commands in container
./vendor/bin/sail artisan migrate
./vendor/bin/sail php artisan test
./vendor/bin/sail npm run dev
```

## Architecture Overview

### Modular Package System
The application uses a **modular architecture** with Webkul packages located in `packages/Webkul/`. Each package is self-contained with its own MVC structure:

- **Core**: Foundation package with base models, repositories, and system functionality
- **Admin**: Backend administration interface and functionality
- **Shop**: Frontend storefront and customer-facing features
- **Product**: Product management, variants, categories, and attributes
- **Customer**: Customer management, authentication, and profiles
- **Sales**: Order management, checkout, and payment processing
- **Category**: Category hierarchy and management
- **Payment**: Payment gateway integrations
- **Shipping**: Shipping methods and logistics
- **Inventory**: Stock management and warehouses
- **DataGrid**: Data table functionality for admin interface

### Key Components

#### Package Structure
Each Webkul package follows this pattern:
```
packages/Webkul/{PackageName}/
├── src/
│   ├── Contracts/      # Interfaces
│   ├── Models/         # Eloquent models
│   ├── Repositories/   # Data access layer
│   ├── Http/           # Controllers and middleware
│   ├── Providers/      # Service providers
│   └── Resources/      # Views and assets
├── config/             # Package configuration
└── tests/              # Package-specific tests
```

#### Repository Pattern
Uses repository pattern for data access:
- Repositories abstract database operations
- Located in each package's `Repositories/` directory
- Follow interface-based design with Contracts

#### Attribute System
- Flexible attribute system for products and categories
- Configurable attribute families
- Support for various attribute types (text, select, date, etc.)

### Database
- MySQL 8.0 with full-text search support
- Elasticsearch for product search and indexing
- Redis for caching and sessions
- Uses migrations for schema management

### Frontend
- Vue.js for admin interface components
- Blade templates for main views
- Vite for asset compilation
- Tailwind CSS for styling
- Responsive design for mobile compatibility

## Custom Features

### Shopify Import Integration
The system includes a custom Shopify product import command (`app/Console/Commands/ImportShopifyProducts.php`) that:
- Imports products from any Shopify store via REST API
- Handles product variants and categories
- Downloads and processes product images
- Supports dry-run mode for testing
- Includes error handling and logging

### Multi-Channel Support
- Configurable sales channels
- Channel-specific product catalogs
- Independent inventory management per channel

### Internationalization
- Multi-language support (Arabic, Chinese, Dutch, English, German, Italian, Persian, Polish, Portuguese, Spanish, Turkish)
- Localized admin interface
- Currency and tax management per locale

## File Structure Highlights

- `app/Console/Commands/` - Custom artisan commands (Shopify import)
- `packages/Webkul/` - Modular package system
- `routes/` - Laravel route definitions
- `resources/` - Frontend assets and views
- `config/` - Laravel configuration files
- `database/` - Migrations and seeders
- `storage/` - Application storage (logs, uploads, cache)

## Development Notes

- Code formatting follows Laravel Pint standards with custom binary operator spacing rules
- Uses PSR-4 autoloading for packages and app code
- Test suite includes both unit and feature tests
- Docker Compose setup includes MySQL, Redis, Elasticsearch, and Kibana
- Uses Laravel Sanctum for API authentication
- Implements proper error handling and logging throughout the application