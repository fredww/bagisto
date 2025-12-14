<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Redirecting to Airwallex</title>
</head>
<body>
<pre id="params" style="white-space: pre-wrap; word-wrap: break-word;"></pre>
<script src="https://static.airwallex.com/components/sdk/v1/index.js"></script>
<script>
  const params = {
    intent_id: '{{ $intentId }}',
    client_secret: '{{ $clientSecret }}',
    currency: '{{ $currency }}',
    country_code: '{{ $countryCode }}',
    successUrl: '{{ $successUrl }}',
    failUrl: '{{ $failUrl }}',
    env: '{{ $env }}'
  };
  document.getElementById('params').textContent = JSON.stringify(params, null, 2);

  const btn = document.createElement('button');
  btn.id = 'pay-now';
  btn.textContent = 'Pay Now';
  btn.style.marginTop = '12px';
  document.body.appendChild(btn);

  const sanitize = v => String(v || '').trim().replace(/^`+|`+$/g, '');

  btn.addEventListener('click', async () => {
    try {
      const { payments } = await window.AirwallexComponentsSDK.init({ env: sanitize(params.env), enabledElements: ['payments'] });
      await payments.redirectToCheckout({
        intent_id: sanitize(params.intent_id),
        client_secret: sanitize(params.client_secret),
        currency: sanitize(params.currency),
        country_code: sanitize(params.country_code),
        successUrl: sanitize(params.successUrl),
        failUrl: sanitize(params.failUrl),
        mode: 'payment'
      });
    } catch (e) {
      alert('Redirect failed: ' + (e && e.message ? e.message : e));
    }
  });
</script>
</body>
</html>
