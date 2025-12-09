<?php

namespace Webkul\Airwallex\Payment;

use Illuminate\Support\Facades\Storage;
use Webkul\Payment\Payment\Payment;

class Airwallex extends Payment
{
    protected $code = 'airwallex';

    public function getRedirectUrl()
    {
        return route('airwallex.redirect');
    }

    public function isAvailable(): bool
    {
        return (bool) $this->getConfigData('active')
            && (bool) $this->getConfigData('client_id')
            && (bool) $this->getConfigData('api_key')
            && (bool) ($this->getConfigData('base_url') ?: 'https://api.airwallex.com');
    }

    public function getImage()
    {
        $url = $this->getConfigData('image');

        return $url ? Storage::url($url) : bagisto_asset('images/money-transfer.png', 'shop');
    }
}
