<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shop Theme Configuration
    |--------------------------------------------------------------------------
    |
    | All the configurations are related to the shop themes.
    |
    */

    'shop-default' => 'default',

    'shop' => [
        'default' => [
            'name'        => 'Default',
            'assets_path' => 'public/themes/shop/default',
            'views_path'  => 'resources/themes/default/views',

            'vite'        => [
                'hot_file'                 => 'shop-default-vite.hot',
                'build_directory'          => 'themes/shop/default/build',
                'package_assets_directory' => 'src/Resources/assets',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Theme Configuration
    |--------------------------------------------------------------------------
    |
    | All the configurations are related to the admin themes.
    |
    */

    'admin-default' => 'default',

    'admin' => [
        'default' => [
            'name'        => 'Default',
            'assets_path' => 'public/themes/admin/default',
            'views_path'  => 'resources/admin-themes/default/views',

            'vite'        => [
                'hot_file'                 => 'admin-default-vite.hot',
                'build_directory'          => 'themes/admin/default/build',
                'package_assets_directory' => 'src/Resources/assets',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Theme Styles
    |--------------------------------------------------------------------------
    |
    | These values are used as fallbacks when no specific theme configuration
    | is saved in the database.
    |
    */

    'default_styles' => [
        'font_family'     => "'Inter', 'Poppins', sans-serif",
        'font_heading'    => "'Playfair Display', 'DM Serif Display', serif",
        'primary_color'   => '#D4A5A5',
        'primary_dark'    => '#B88B8B',
        'primary_light'   => '#F5E6E6',
        'secondary_color' => '#8B7E74',
        'accent_color'    => '#C9A690',
        'neutral_dark'    => '#2D2D2D',
        'neutral_medium'  => '#6B6B6B',
        'neutral_light'   => '#F8F5F2',
        'success_color'   => '#A8C5A3',
        'warning_color'   => '#E8C4A0',
        'error_color'     => '#D4A5A5',
        'button_bg'       => '#D4A5A5',
        'button_text'     => '#ffffff',
        'nav_bg'          => '#ffffff',
        'nav_text'        => '#2D2D2D',
        'footer_bg'       => '#F5E6E6',
        'footer_text'     => '#2D2D2D',
    ],
];
