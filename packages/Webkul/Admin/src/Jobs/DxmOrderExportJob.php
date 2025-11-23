<?php

namespace Webkul\Admin\Jobs;

use App\Models\DxmOrderExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Product\Repositories\ProductRepository;

class DxmOrderExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected int $taskId, protected ?string $storeAccount = null)
    {
    }

    public function handle(): void
    {
        $task = DxmOrderExport::findOrFail($this->taskId);

        $date = $task->export_date;

        $ordersQuery = Order::query()
            ->join('fortune_payments', 'fortune_payments.order_no', '=', 'orders.increment_id')
            ->where('fortune_payments.status', 'success')
            ->whereDate('orders.created_at', $date)
            ->select('orders.*');

        $orderIds = $ordersQuery->pluck('orders.id');

        $itemsQuery = OrderItem::query()
            ->whereIn('order_id', $orderIds)
            ->whereNull('parent_id');

        $task->total_rows = (int) $itemsQuery->count();
        $task->processed_rows = 0;
        $task->state = 'processing';
        $task->save();

        $dir = 'exports/dxm/'.date('Ymd');
        Storage::disk('local')->makeDirectory($dir);

        $filename = '店小秘订单_'.date('Ymd_His').'.xlsx';
        $path = $dir.'/'.$filename;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $header = [
            '订单号','店铺账号','sku','属性','数量','单价','总运费','币种','买家指定物流','发货仓库','买家姓名','地址1','地址2','区县','城市','省/州','国家二字码','邮编','电话','手机','E-mail','买家税号','门牌号','公司名','买家备注','图片网址','售出链接','中文报关名','英文报关名','申报金额（USD）','出口申报金额（USD）','出口企业名称','企业信用代码','申报重量（g）','材质','用途','海关编码','进口海关编码','报关属性','卖家税号（IOSS）','下单时间（北京时间）','客服备注','拣货备注'
        ];
        $sheet->fromArray($header, null, 'A1');
$rowIndex = 2;
$sheet->getStyle('C:C')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        $itemsQuery->orderBy('order_id')->chunk(500, function ($items) use ($sheet, &$rowIndex, $task) {
            foreach ($items as $item) {
                $order = $item->order;
                $shipping = $order->shipping_address;

                $attributes = '';
                $attrLines = [];
                $attrLines[] = trim((string) ($item->name ?? ($item->product->name ?? '')));

                if (is_array($item->additional)) {
                    $options = $item->additional['attributes'] ?? $item->additional['options'] ?? [];
                    if (is_array($options)) {
                        foreach ($options as $opt) {
                            if (! is_array($opt)) {
                                continue;
                            }

                            if (isset($opt['attribute_name'], $opt['option_label'])) {
                                $label = trim((string) $opt['attribute_name']);
                                $value = trim((string) $opt['option_label']);
                            } elseif (isset($opt['label'], $opt['value_label'])) {
                                $label = trim((string) $opt['label']);
                                $value = trim((string) $opt['value_label']);
                            } else {
                                $label = trim((string) ($opt['label'] ?? ''));
                                $value = trim((string) ($opt['value'] ?? ''));
                            }

                            if ($label !== '' && $value !== '') {
                                $attrLines[] = $label . ' : ' . $value;
                            }
                        }
                    }
                }

                if (count($attrLines) === 1) {
                    try {
                        $productForAttr = $item->product;
                        $selectedId = is_array($item->additional) ? ($item->additional['selected_configurable_option'] ?? null) : null;
                        if ($selectedId) {
                            $repo = app(ProductRepository::class);
                            $selectedProduct = $repo->find($selectedId);
                            if ($selectedProduct) {
                                $productForAttr = $selectedProduct;
                            }
                        }

                        if ($productForAttr && $productForAttr->parent) {
                            foreach ($productForAttr->parent->super_attributes as $attribute) {
                                $label = trim((string) ($attribute->admin_name ?? ''));
                                $valueId = $productForAttr->getCustomAttributeValue($attribute);
                                $valueLabel = '';
                                if (! is_null($valueId)) {
                                    $option = $attribute->options()->where('id', $valueId)->first();
                                    if ($option) {
                                        $valueLabel = trim((string) ($option->label ?? $option->admin_name ?? ''));
                                    }
                                }
                                if ($label !== '' && $valueLabel !== '') {
                                    $attrLines[] = $label . ' : ' . $valueLabel;
                                }
                            }
                        }
                    } catch (\Throwable $e) {}
                }

                $attributes = implode("\n", array_filter($attrLines));

                $currency = $order->order_currency_code ?: 'USD';

                $storeAccount = $this->storeAccount ?? ((string) (core()->getConfigData('sales.order_settings.dxm_export.store_account_default') ?? '')) ?: null;

                // 获取商品主图 URL
                $imageUrl = '';
                try {
                    $productForImage = $item->product;
                    $selectedId = null;
                    if (is_array($item->additional)) {
                        $selectedId = $item->additional['selected_configurable_option'] ?? null;
                    }
                    if ($selectedId) {
                        $repo = app(ProductRepository::class);
                        $selectedProduct = $repo->find($selectedId);
                        if ($selectedProduct) {
                            $productForImage = $selectedProduct;
                        }
                    }

                    $baseImage = product_image()->getProductBaseImage($productForImage);
                    if (is_array($baseImage)) {
                        $imageUrl = (string) ($baseImage['original_image_url'] ?? ($baseImage['large_image_url'] ?? ''));
                    }
                } catch (\Throwable $e) {
                    $imageUrl = '';
                }

                $row = [
                    $order->increment_id,
                    $storeAccount ?: $order->channel_name,
                    $item->sku,
                    $attributes,
                    max(1, (int) $item->qty_ordered),
                    (string) $item->base_price,
                    (string) $order->base_shipping_amount,
                    $currency,
                    $order->shipping_title,
                    '',
                    ($shipping?->first_name ?? '').' '.($shipping?->last_name ?? ''),
                    $shipping?->address ?? '',
                    $shipping?->address2 ?? '',
                    '',
                    $shipping?->city ?? '',
                    $shipping?->state ?? '',
                    $shipping?->country ?? '',
                    $shipping?->postcode ?? '',
                    $shipping?->phone ?? '',
                    '',
                    $shipping?->email ?? $order->customer_email ?? '',
                    $shipping?->vat_id ?? '',
                    '',
                    $shipping?->company_name ?? '',
                    '',
                    $imageUrl,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',//进口海关编码
                    '',
                    '',
                ];

                $sheet->fromArray($row, null, 'A'.$rowIndex);
                $sheet->setCellValueExplicit('C'.$rowIndex, (string) $item->sku, DataType::TYPE_STRING);
                $sheet->setCellValue('Z'.$rowIndex, $imageUrl);
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
