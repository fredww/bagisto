<?php

namespace Tests\Unit;

use Webkul\Airwallex\Services\AirwallexService;
use PHPUnit\Framework\TestCase;

class AirwallexSignatureTest extends TestCase
{
    public function test_verify_webhook_signature_with_hex(): void
    {
        $service = new AirwallexService();
        $secret = 'test_secret';
        $timestamp = '1734150000000';
        $body = '{"example":"data"}';

        $expectedHex = hash_hmac('sha256', $timestamp.$body, $secret, false);
        $this->assertTrue($service->verifyWebhookSignature($timestamp, $expectedHex, $secret, $body));
    }

    public function test_verify_webhook_signature_with_base64(): void
    {
        $service = new AirwallexService();
        $secret = 'test_secret';
        $timestamp = '1734150000000';
        $body = '{"example":"data"}';

        $expectedB64 = base64_encode(hash_hmac('sha256', $timestamp.$body, $secret, true));
        $this->assertTrue($service->verifyWebhookSignature($timestamp, $expectedB64, $secret, $body));
    }

    public function test_verify_webhook_signature_invalid(): void
    {
        $service = new AirwallexService();
        $secret = 'test_secret';
        $timestamp = '1734150000000';
        $body = '{"example":"data"}';

        $this->assertFalse($service->verifyWebhookSignature($timestamp, 'invalid', $secret, $body));
    }
}

