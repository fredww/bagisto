@php
    $enabled     = (bool) core()->getConfigData('general.content.pinterest.enabled');
    $tagId       = (string) core()->getConfigData('general.content.pinterest.tag_id');
    $sendEmail   = (bool) core()->getConfigData('general.content.pinterest.send_email');
    $debug       = (bool) core()->getConfigData('general.content.pinterest.debug');
    $currency    = core()->getCurrentCurrency()?->code ?? 'USD';
    $customer    = auth()->guard('customer')->user();
    $email       = $customer?->email;
    $hashedEmail = $email ? hash('sha256', strtolower(trim($email))) : null;
@endphp

@if ($enabled && ($tagId ?: '2613588384180'))
    <script>
    !function(e){if(!window.pintrk){window.pintrk=function(){window.pintrk.queue.push(Array.prototype.slice.call(arguments))};var n=window.pintrk;n.queue=[],n.version="3.0";var t=document.createElement("script");t.async=!0,t.src=e;var r=document.getElementsByTagName("script")[0];r.parentNode.insertBefore(t,r)}}("https://s.pinimg.com/ct/core.js");
    pintrk('load', '{{ $tagId ?: '2613588384180' }}', {
        @if ($sendEmail && $email)
        em: '{{ $email }}'
        @endif
    });
    pintrk('page');
    </script>

    <script>
        window.__PIN_CONFIG__ = {
            enabled: true,
            tagId: '{{ $tagId ?: '2613588384180' }}',
            debug: {{ $debug ? 'true' : 'false' }},
            currency: '{{ $currency }}'
        };
    </script>

    <noscript>
        <img height="1" width="1" style="display:none;" alt="" src="https://ct.pinterest.com/v3/?event=init&tid={{ urlencode($tagId ?: '2613588384180') }}@if($hashedEmail)&pd[em]={{ urlencode($hashedEmail) }}@endif&noscript=1" />
    </noscript>
@endif
