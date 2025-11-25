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
            href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=DM+Serif+Display&display=swap"
        />

        <link
            rel="stylesheet"
            href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=DM+Serif+Display&display=swap"
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
                --theme-font: {!! core()->getConfigData('general.design.theme.font_family') ?: 'Poppins, sans-serif' !!};
                --theme-primary: {!! core()->getConfigData('general.design.theme.primary_color') ?: '#1f2937' !!};
                --btn-bg: {!! core()->getConfigData('general.design.theme.button_bg') ?: '#1f2937' !!};
                --btn-text: {!! core()->getConfigData('general.design.theme.button_text') ?: '#ffffff' !!};
                --nav-bg: {!! core()->getConfigData('general.design.theme.nav_bg') ?: '#ffffff' !!};
                --nav-text: {!! core()->getConfigData('general.design.theme.nav_text') ?: '#1f2937' !!};
                --footer-bg: {!! core()->getConfigData('general.design.theme.footer_bg') ?: '#fef3c7' !!};
                --footer-text: {!! core()->getConfigData('general.design.theme.footer_text') ?: '#1f2937' !!};
            }

            body { font-family: var(--theme-font) !important; }
            .primary-button { background-color: var(--btn-bg) !important; color: var(--btn-text) !important; border-color: var(--btn-bg) !important; }
            header { background-color: var(--nav-bg) !important; color: var(--nav-text) !important; }
            header a, header span, header p { color: var(--nav-text) !important; }
            footer { background-color: var(--footer-bg) !important; color: var(--footer-text) !important; }
            footer a, footer p, footer h3, footer li { color: var(--footer-text) !important; }
        </style>

        @if(core()->getConfigData('general.content.speculation_rules.enabled'))
            <script type="speculationrules">
                @json(core()->getSpeculationRules(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            </script>
        @endif

        {{-- Inject Google Ads & GA4 tracking scripts (auto-loaded from admin config) --}}
        @include('shop::components.tracking.google')

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

        {!! view_render_event('bagisto.shop.layout.vue-app-mount.after') !!}

        <script type="text/javascript">
            {!! core()->getConfigData('general.content.custom_scripts.custom_javascript') !!}
        </script>
    </body>
</html>
