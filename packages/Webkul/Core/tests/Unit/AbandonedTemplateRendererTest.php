<?php

namespace Webkul\Core\Tests\Unit;

use Tests\TestCase;
use Webkul\Marketing\Services\AbandonedTemplateRenderer;
use Webkul\Sales\Contracts\Order as OrderContract;

class AbandonedTemplateRendererTest extends TestCase
{

    public function test_placeholders_are_replaced_in_subject_and_body(): void
    {
        $order = new class implements OrderContract {
            public $customer_first_name = 'Alex';
            public $customer_full_name  = 'Alex Doe';
            public $items;

            public function __construct()
            {
                $item          = new \stdClass();
                $item->name    = 'Sample Product';
                $item->qty_ordered = 1;
                $item->base_total  = 2999;
                $this->items   = [$item];
            }
        };

        $payload = [
            'subject' => 'Hi [First Name]',
            'body'    => 'Items: [Product items] Email: [support email] Store: [Store Name] [Button: Securely Complete My Order]',
        ];

        $renderer = app(AbandonedTemplateRenderer::class);

        $rendered = $renderer->render($order, $payload);

        $this->assertStringContainsString('Hi Alex', $rendered['subject']);
        $this->assertStringContainsString('Sample Product', $rendered['body']);
        $this->assertStringContainsString('Securely Complete My Order', $rendered['body']);
        $this->assertStringContainsString('<a href="', $rendered['body']);
    }
}
