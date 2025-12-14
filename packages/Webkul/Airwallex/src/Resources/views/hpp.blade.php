<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Redirecting to Airwallex</title>
</head>
<body>
<script src="https://static.airwallex.com/components/sdk/v1/index.js"></script>
<script>
  const sanitize = v => String(v || '').trim().replace(/^`+|`+$/g, '');
  (async () => {
    try {
      const { payments } = await window.AirwallexComponentsSDK.init({ env: sanitize('{{ $env }}'), enabledElements: ['payments'] });
      await payments.redirectToCheckout({
        intent_id: sanitize('{{ $intentId }}'),
        client_secret: sanitize('{{ $clientSecret }}'),
        currency: sanitize('{{ $currency }}'),
        country_code: sanitize('{{ $countryCode }}'),
        successUrl: sanitize('{{ $successUrl }}'),
        failUrl: sanitize('{{ $failUrl }}'),
        mode: 'payment'
      });
    } catch (e) {
      window.location.href = sanitize('{{ $failUrl }}');
    }
  })();
</script>
</body>
</html>
