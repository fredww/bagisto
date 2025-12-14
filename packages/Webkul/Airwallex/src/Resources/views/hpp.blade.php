<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Redirecting to Airwallex</title>
</head>
<body>
<script src="https://js.airwallex.com/v1/airwallex.js"></script>
<script>
(async function () {
  try {
    const payment = await window.Airwallex.init({ env: '{{ $env }}', enabledElements: ['payments'] });
    await payment.redirectToCheckout({
      intent_id: '{{ $intentId }}',
      client_secret: '{{ $clientSecret }}',
      currency: '{{ $currency }}',
      country_code: '{{ $countryCode }}'
    });
  } catch (e) {
    window.location.href = '{{ $failUrl }}';
  }
})();
</script>
</body>
</html>

