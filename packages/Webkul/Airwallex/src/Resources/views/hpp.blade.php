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
(async function () {
  try {
    const { payments } = await window.Airwallex.init({ env: '{{ $env }}', enabledElements: ['payments'] });
    await payments.redirectToCheckout({
      intent_id: '{{ $intentId }}',
      client_secret: '{{ $clientSecret }}',
      currency: '{{ $currency }}',
      country_code: '{{ $countryCode }}',
      successUrl: '{{ $successUrl }}'
    });
  } catch (e) {
    window.location.href = '{{ $failUrl }}';
  }
})();
</script>
</body>
</html>
