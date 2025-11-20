<?php

use App\Models\FortunePayment;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\OrderItem;

use function Pest\Laravel\postJson;
use function Pest\Laravel\get;

it('starts, processes and downloads 店小秘订单导出', function () {
    $this->loginAsAdmin();

    $order = Order::factory()->create([
        'increment_id' => 'T202511200001',
        'order_currency_code' => 'USD',
        'channel_name' => 'Default',
    ]);

    OrderAddress::factory()->create([
        'order_id' => $order->id,
        'address_type' => OrderAddress::ADDRESS_TYPE_SHIPPING,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'sku' => 'SKU-1',
        'qty_ordered' => 1,
        'base_price' => 10,
    ]);

    FortunePayment::query()->create([
        'order_no' => $order->increment_id,
        'invoice_id' => 'INV-1',
        'currency' => 'USD',
        'amount' => 10,
        'status' => 'success',
    ]);

    $start = postJson(route('admin.sales.orders.dxm_export.start'), [
        'date' => $order->created_at->toDateString(),
    ]);

    $start->assertOk();

    $taskId = $start->json('id');

    $status = get(route('admin.sales.orders.dxm_export.status', $taskId));

    $status->assertOk();

    expect(in_array($status->json('state'), ['processing', 'completed']))->toBeTrue();

    // Wait a bit for sync queue processing.
    $status = get(route('admin.sales.orders.dxm_export.status', $taskId));

    $downloadUrl = $status->json('downloadUrl');

    expect($downloadUrl)->not->toBeNull();

    $download = get($downloadUrl);

    $download->assertOk();
});