<?php

namespace Webkul\Admin\Jobs;

use App\Models\DxmOrderExport;
use App\Models\FortunePayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Illuminate\Support\Carbon;

class WlyOrderExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected int $taskId, protected ?string $startDate = null, protected ?string $endDate = null)
    {
    }

    public function handle(): void
    {
        $task = DxmOrderExport::findOrFail($this->taskId);

        $ordersQuery = Order::query()
            ->whereIn('orders.status', [Order::STATUS_PROCESSING, Order::STATUS_COMPLETED]);

        if ($this->startDate || $this->endDate) {
            $start = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : null;
            $end = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : null;

            if ($start && $end) {
                $ordersQuery->whereBetween('orders.created_at', [$start, $end]);
            } elseif ($start) {
                $ordersQuery->where('orders.created_at', '>=', $start);
            } elseif ($end) {
                $ordersQuery->where('orders.created_at', '<=', $end);
            }
        }

        $task->total_rows = (int) $ordersQuery->count();
        $task->processed_rows = 0;
        $task->state = 'processing';
        $task->save();

        $dir = 'exports/wly/'.date('Ymd');
        Storage::disk('local')->makeDirectory($dir);

        $filename = '万里汇订单_'.date('Ymd_His').'.xlsx';
        $path = $dir.'/'.$filename;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $header = [
            'Order ID',
            'Paid Date',
            'Order Total',
            'Currency Code',
            'Product Title',
            'Product Quantity',
            'Buyer Name/Buyer ID',
            'Shipping Address',
        ];
        $sheet->fromArray($header, null, 'A1');

        $rowIndex = 2;

        $ordersQuery->orderBy('orders.id')->chunk(200, function ($orders) use ($sheet, &$rowIndex, $task) {
            foreach ($orders as $order) {

                $paidAt = $order->created_at;

                $currency = $order->order_currency_code ?: 'USD';

                $rootItems = $order->items()->whereNull('parent_id')->get();
                $firstItem = $rootItems->first();
                $productTitle = '';
                if ($firstItem) {
                    $productTitle = trim((string) ($firstItem->name ?? ($firstItem->product->name ?? '')));
                }
                $productQty = (int) ($rootItems->sum(function ($i) {
                    return (int) $i->qty_ordered;
                }));

                $buyerName = trim(((string) ($order->shipping_address?->first_name ?? '')) . ' ' . ((string) ($order->shipping_address?->last_name ?? '')));
                if ($buyerName === '') {
                    $buyerName = trim(((string) ($order->customer_first_name ?? '')) . ' ' . ((string) ($order->customer_last_name ?? '')));
                }
                $buyerId = (string) ($order->customer_email ?? $order->customer_id ?? '');

                $shipping = $order->shipping_address;
                $addressParts = [];
                if ($shipping) {
                    $addressParts[] = (string) ($shipping->address ?? '');
                    $addressParts[] = (string) ($shipping->address2 ?? '');
                    $addressParts[] = (string) ($shipping->city ?? '');
                    $addressParts[] = (string) ($shipping->state ?? '');
                    $addressParts[] = (string) ($shipping->country ?? '');
                    $addressParts[] = (string) ($shipping->postcode ?? '');
                }

                $row = [
                    $order->increment_id,
                    $paidAt ? $paidAt->format('Y/m/d') : '',
                    (string) $order->grand_total,
                    $currency,
                    $productTitle,
                    $productQty,
                    ($buyerName !== '' || $buyerId !== '') ? ($buyerName . '/' . $buyerId) : '',
                    trim(implode(', ', array_filter($addressParts))),
                ];

                $sheet->fromArray($row, null, 'A'.$rowIndex);
                $rowIndex++;

                $task->processed_rows++;
                $task->save();
            }
        });

        $writer = new Xlsx($spreadsheet);
        $writer->save(Storage::disk('local')->path($path));

        $task->file_path = $path;
        $task->state = 'completed';
        $task->completed_at = now();
        $task->save();
    }
}
