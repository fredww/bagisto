@php
    // 读取后台配置以注入 GA4 和 Google Ads 跟踪代码
    /** Read admin configuration to inject GA4 and Google Ads tracking code */
    $ga4Enabled       = (bool) core()->getConfigData('general.content.analytics.ga4_enabled');
    $measurementId    = (string) core()->getConfigData('general.content.analytics.ga4_measurement_id');
    $ga4ApiSecret     = (string) core()->getConfigData('general.content.analytics.ga4_api_secret');
    $adsEnabled       = (bool) core()->getConfigData('general.content.analytics.ads_enabled');
    $adsConversionId  = (string) core()->getConfigData('general.content.analytics.ads_conversion_id');
    $adsPurchaseLabel = (string) core()->getConfigData('general.content.analytics.ads_purchase_label');
    $debugMode        = (bool) core()->getConfigData('general.content.analytics.debug');
    $currency         = core()->getCurrentCurrency()?->code ?? 'USD';
    $scriptId         = $ga4Enabled && $measurementId ? $measurementId : ($adsEnabled && $adsConversionId ? $adsConversionId : null);
@endphp

@if ($scriptId)
    <!-- Load Google gtag library -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $scriptId }}"></script>
    <script>
        // Initialize gtag and configure GA4/Ads properties
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);} // internal: push to dataLayer
        gtag('js', new Date());

        @if ($ga4Enabled && $measurementId)
        gtag('config', '{{ $measurementId }}', { 'debug_mode': {{ $debugMode ? 'true' : 'false' }} });
        @endif

        @if ($adsEnabled && $adsConversionId)
        gtag('config', '{{ $adsConversionId }}');
        @endif

        // Expose a front-end config object for the analytics plugin
        window.__GA_CONFIG__ = {
            ga4Enabled: {{ $ga4Enabled ? 'true' : 'false' }},
            measurementId: '{{ $measurementId }}',
            ga4ApiSecret: '{{ $ga4ApiSecret }}',
            adsEnabled: {{ $adsEnabled ? 'true' : 'false' }},
            adsConversionId: '{{ $adsConversionId }}',
            adsPurchaseLabel: '{{ $adsPurchaseLabel }}',
            debug: {{ $debugMode ? 'true' : 'false' }},
            currency: '{{ $currency }}'
        };

        if (window.__GA_CONFIG__.debug) {
            console.info('[Analytics] gtag initialized:', {
                ga4: window.__GA_CONFIG__.measurementId,
                ads: window.__GA_CONFIG__.adsConversionId,
            });
        }
    </script>
@endif