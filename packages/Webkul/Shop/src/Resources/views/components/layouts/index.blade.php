@props([
    'hasHeader'  => true,
    'hasFeature' => true,
    'hasFooter'  => true,
])

<!DOCTYPE html>

<html
    lang="{{ app()->getLocale() }}"
    dir="{{ core()->getCurrentLocale()->direction }}"
>
    <head>

        {!! view_render_event('bagisto.shop.layout.head.before') !!}

        <title>{{ $title ?? '' }}</title>

        <meta charset="UTF-8">

        <meta
            http-equiv="X-UA-Compatible"
            content="IE=edge"
        >
        <meta
            http-equiv="content-language"
            content="{{ app()->getLocale() }}"
        >

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >
        <meta
            name="base-url"
            content="{{ url()->to('/') }}"
        >
        <meta
            name="currency"
            content="{{ core()->getCurrentCurrency()->toJson() }}"
        >

        @stack('meta')

        <link
            rel="icon"
            sizes="16x16"
            href="{{ core()->getCurrentChannel()->favicon_url ?? bagisto_asset('images/favicon.ico') }}"
        />

        @bagistoVite(['src/Resources/assets/css/app.css', 'src/Resources/assets/js/app.js'])

        <link
            rel="preconnect"
            href="https://fonts.googleapis.com"
            crossorigin
        />

        <link
            rel="preconnect"
            href="https://fonts.gstatic.com"
            crossorigin
        />

        <link
            rel="preload" as="style"
            href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=DM+Serif+Display&family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap"
        />

        <link
            rel="stylesheet"
            href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=DM+Serif+Display&family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap"
        />

        @stack('styles')

        <!-- Toggle Switch CSS -->
        <link
            rel="stylesheet"
            href="{{ asset('themes/shop/default/assets/css/toggle-switch.css') }}"
        />

        <style>
            {!! core()->getConfigData('general.content.custom_scripts.custom_css') !!}
            
            :root {
                /* ===== Typography ===== */
                --font-heading: {!! core()->getConfigData('general.design.theme.font_heading') ?: config('themes.default_styles.font_heading') !!};
                --font-body: {!! core()->getConfigData('general.design.theme.font_family') ?: config('themes.default_styles.font_family') !!};
                
                /* ===== Primary Colors - Rose Gold ===== */
                --primary-color: {!! core()->getConfigData('general.design.theme.primary_color') ?: config('themes.default_styles.primary_color') !!};
                --primary-dark: {!! core()->getConfigData('general.design.theme.primary_dark') ?: config('themes.default_styles.primary_dark') !!};
                --primary-light: {!! core()->getConfigData('general.design.theme.primary_light') ?: config('themes.default_styles.primary_light') !!};
                
                /* ===== Secondary & Accent Colors ===== */
                --secondary-color: {!! core()->getConfigData('general.design.theme.secondary_color') ?: config('themes.default_styles.secondary_color') !!};
                --accent-color: {!! core()->getConfigData('general.design.theme.accent_color') ?: config('themes.default_styles.accent_color') !!};

                /* ===== Button Colors ===== */
                --btn-bg: var(--primary-color);
                --btn-text: #ffffff;
                --btn-hover: var(--primary-dark);
            }

            /* Universal Utilities */
            .text-primary { color: var(--primary-color) !important; }
            .hover-text-primary:hover { color: var(--primary-color) !important; }
            .border-primary { border-color: var(--primary-color) !important; }
            .hover-border-primary:hover { border-color: var(--primary-color) !important; }
            .bg-primary { background-color: var(--primary-color) !important; }
            .hover-bg-primary:hover { background-color: var(--primary-dark) !important; }
                
                :root {
                /* ===== Neutral Colors ===== */
                --neutral-dark: {!! core()->getConfigData('general.design.theme.neutral_dark') ?: config('themes.default_styles.neutral_dark') !!};
                --neutral-medium: {!! core()->getConfigData('general.design.theme.neutral_medium') ?: config('themes.default_styles.neutral_medium') !!};
                --neutral-light: {!! core()->getConfigData('general.design.theme.neutral_light') ?: config('themes.default_styles.neutral_light') !!};
                --white: #FFFFFF;
                
                /* ===== Functional Colors ===== */
                --success: {!! core()->getConfigData('general.design.theme.success_color') ?: config('themes.default_styles.success_color') !!};
                --warning: {!! core()->getConfigData('general.design.theme.warning_color') ?: config('themes.default_styles.warning_color') !!};
                --error: {!! core()->getConfigData('general.design.theme.error_color') ?: config('themes.default_styles.error_color') !!};
                
                /* ===== Legacy Variables (for backward compatibility) ===== */
                --theme-font: var(--font-body);
                --theme-primary: var(--primary-color);
                --btn-bg: {!! core()->getConfigData('general.design.theme.button_bg') ?: config('themes.default_styles.button_bg') !!};
                --btn-text: {!! core()->getConfigData('general.design.theme.button_text') ?: config('themes.default_styles.button_text') !!};
                --nav-bg: {!! core()->getConfigData('general.design.theme.nav_bg') ?: config('themes.default_styles.nav_bg') !!};
                --nav-text: {!! core()->getConfigData('general.design.theme.nav_text') ?: config('themes.default_styles.nav_text') !!};
                --footer-bg: {!! core()->getConfigData('general.design.theme.footer_bg') ?: config('themes.default_styles.footer_bg') !!};
                --footer-text: {!! core()->getConfigData('general.design.theme.footer_text') ?: config('themes.default_styles.footer_text') !!};
                
                /* ===== Spacing System ===== */
                --spacing-xs: 0.25rem;   /* 4px */
                --spacing-sm: 0.5rem;    /* 8px */
                --spacing-md: 1rem;      /* 16px */
                --spacing-lg: 1.5rem;    /* 24px */
                --spacing-xl: 2rem;      /* 32px */
                --spacing-2xl: 3rem;     /* 48px */
                --spacing-3xl: 4rem;     /* 64px */
                
                /* ===== Border Radius ===== */
                --radius-sm: 4px;
                --radius-md: 8px;
                --radius-lg: 12px;
                --radius-xl: 16px;
                --radius-full: 9999px;
                
                /* ===== Shadows ===== */
                --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                
                /* ===== Layout Config ===== */
                --grid-columns: {!! core()->getConfigData('general.design.theme_settings.product_grid_columns') ?: '4' !!};
            }

            /* ===== Base Typography ===== */
            body { 
                font-family: var(--font-body) !important;
                color: var(--neutral-dark);
            }
            
            h1, h2, h3, h4, h5, h6 {
                font-family: var(--font-heading) !important;
                color: var(--neutral-dark);
            }
            
            /* ===== Premium Button Styles ===== */
            .primary-button,
            button[type="submit"]:not(.secondary-button):not(.primary-solid-button),
            .btn-primary {
                background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%) !important;
                color: var(--btn-text) !important;
                border: none !important;
                border-radius: var(--radius-md) !important;
                padding: 12px 32px !important;
                min-height: 48px !important;
                font-weight: 600 !important;
                letter-spacing: 0.3px !important;
                box-shadow: var(--shadow-sm) !important;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                cursor: pointer !important;
            }
            
            .primary-button:hover,
            button[type="submit"]:not(.secondary-button):hover,
            .btn-primary:hover {
                background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%) !important;
                box-shadow: var(--shadow-md) !important;
                transform: translateY(-2px) !important;
            }

            .primary-button:focus,
            .btn-primary:focus,
            a.primary-button:focus,
            button[type="submit"]:not(.secondary-button):focus {
                color: var(--btn-text) !important;
                background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%) !important;
                box-shadow: 0 0 0 3px rgba(212, 165, 165, 0.25) !important;
                outline: none !important;
            }
            
            .secondary-button,
            .btn-secondary {
                background: transparent !important;
                color: var(--primary-color) !important;
                border: 2px solid var(--primary-color) !important;
                border-radius: var(--radius-md) !important;
                padding: 10px 30px !important;
                min-height: 48px !important;
                font-weight: 600 !important;
                transition: all 0.3s ease !important;
            }

            /* Solid Primary Button (pure color, not gradient) */
            .primary-solid-button,
            .btn-primary-solid {
                background: var(--primary-color) !important;
                color: var(--btn-text) !important;
                border: none !important;
                border-radius: var(--radius-md) !important;
                padding: 12px 12px !important;
                min-height: 48px !important;
                font-weight: 600 !important;
                letter-spacing: 0.3px !important;
                box-shadow: var(--shadow-sm) !important;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                cursor: pointer !important;
            }
            .primary-solid-button:hover,
            .btn-primary-solid:hover {
                background: var(--primary-dark) !important;
                box-shadow: var(--shadow-md) !important;
                transform: translateY(-2px) !important;
                color: var(--btn-text) !important;
            }
            
            .secondary-button:hover,
            .btn-secondary:hover {
                background: var(--primary-light) !important;
                border-color: var(--primary-dark) !important;
                color: var(--primary-dark) !important;
            }
            
            /* ===== Header Styles ===== */
            header {
                background-color: var(--nav-bg) !important;
                color: var(--nav-text) !important;
                box-shadow: var(--shadow-sm) !important;
                border-bottom: 1px solid rgba(212, 165, 165, 0.1) !important;
            }
            
            header a,
            header p,
            header button {
                color: var(--nav-text) !important;
                transition: color 0.3s ease !important;
            }
            
            header a:hover,
            header button:hover {
                color: var(--primary-color) !important;
            }
            
            /* ===== Footer Styles ===== */
            footer {
                background: linear-gradient(180deg, var(--footer-bg) 0%, rgba(245, 230, 230, 0.5) 100%) !important;
                color: var(--footer-text) !important;
                padding: var(--spacing-3xl) 0 var(--spacing-xl) 0 !important;
            }
            
            footer a,
            footer p,
            footer h3,
            footer h4,
            footer li {
                color: var(--footer-text) !important;
                transition: color 0.3s ease !important;
            }
            
            footer a:hover {
                color: var(--primary-dark) !important;
            }
            
            /* ===== Product Card Enhancements ===== */
            .product-card,
            [class*="product"] {
                border-radius: var(--radius-lg) !important;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                border: 1px solid rgba(212, 165, 165, 0.15) !important;
            }
            
            .product-card:hover,
            [class*="product"]:hover {
                box-shadow: var(--shadow-lg) !important;
                transform: translateY(-4px) !important;
                border-color: var(--primary-light) !important;
            }
            
            /* ===== Links ===== */
            a {
                color: var(--primary-color);
                transition: color 0.3s ease;
            }
            
            a:hover {
                color: var(--primary-dark);
            }
            
            /* ===== Form Elements ===== */
            input[type="text"],
            input[type="email"],
            input[type="password"],
            input[type="number"],
            textarea,
            select {
                border: 1px solid rgba(107, 107, 107, 0.2) !important;
                border-radius: var(--radius-md) !important;
                transition: all 0.3s ease !important;
            }
            
            input:focus,
            textarea:focus,
            select:focus {
                border-color: var(--primary-color) !important;
                box-shadow: 0 0 0 3px rgba(212, 165, 165, 0.1) !important;
                outline: none !important;
            }
            
            /* ===== Premium Animations ===== */
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            @keyframes shimmer {
                0% {
                    background-position: -1000px 0;
                }
                100% {
                    background-position: 1000px 0;
                }
            }
            
            /* ===== Utility Classes ===== */
            .text-primary { color: var(--primary-color) !important; }
            .text-secondary { color: var(--secondary-color) !important; }
            .text-accent { color: var(--accent-color) !important; }
            .text-cart-red { color: #ff6600 !important; }
            .bg-primary { background-color: var(--primary-color) !important; }
            .bg-primary-light { background-color: var(--primary-light) !important; }
            .bg-neutral-light { background-color: var(--neutral-light) !important; }
        </style>


        @if(core()->getConfigData('general.content.speculation_rules.enabled'))
            <script type="speculationrules">
                @json(core()->getSpeculationRules(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            </script>
        @endif

        {{-- Inject Google Ads & GA4 tracking scripts (auto-loaded from admin config) --}}
        @include('shop::components.tracking.google')
        @include('shop::components.tracking.pinterest')

        {!! view_render_event('bagisto.shop.layout.head.after') !!}

    </head>

    <body>
        {!! view_render_event('bagisto.shop.layout.body.before') !!}

        <a
            href="#main"
            class="skip-to-main-content-link"
        >
            Skip to main content
        </a>

        <div id="app">
            <!-- Flash Message Blade Component -->
            <x-shop::flash-group />

            <!-- Confirm Modal Blade Component -->
            <x-shop::modal.confirm />

            <!-- Page Header Blade Component -->
            @if ($hasHeader)
                <x-shop::layouts.header />
            @endif

            @if(
                core()->getConfigData('general.gdpr.settings.enabled')
                && core()->getConfigData('general.gdpr.cookie.enabled')
            )
                <x-shop::layouts.cookie />
            @endif

            {!! view_render_event('bagisto.shop.layout.content.before') !!}

            <!-- Page Content Blade Component -->
            <main id="main" class="bg-white">
                {{ $slot }}
            </main>

            {!! view_render_event('bagisto.shop.layout.content.after') !!}


            <!-- Page Services Blade Component -->
            @if ($hasFeature)
                <x-shop::layouts.services />
            @endif

            <!-- Page Footer Blade Component -->
            @if ($hasFooter)
                <x-shop::layouts.footer />
            @endif
        </div>

        {!! view_render_event('bagisto.shop.layout.body.after') !!}

        @stack('scripts')

        {!! view_render_event('bagisto.shop.layout.vue-app-mount.before') !!}
        <script>
            /**
             * Load event, the purpose of using the event is to mount the application
             * after all of our `Vue` components which is present in blade file have
             * been registered in the app. No matter what `app.mount()` should be
             * called in the last.
             */
            window.addEventListener("load", function (event) {
                app.mount("#app");
            });
        </script>

        @if (session()->has('pinterest_lead'))
            <script>
                if (window.PinterestIntegration) {
                    window.PinterestIntegration.trackLead({ lead_type: 'Newsletter' });
                }
            </script>
        @endif

        {!! view_render_event('bagisto.shop.layout.vue-app-mount.after') !!}

        <script type="text/javascript">
            {!! core()->getConfigData('general.content.custom_scripts.custom_javascript') !!}
        </script>
    </body>
</html>
