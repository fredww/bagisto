<?php

namespace Webkul\Admin\Http\Controllers\Communication;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Webkul\Admin\DataGrids\Communication\EmailLogDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Models\EmailLog;
use Webkul\Core\Repositories\EmailLogRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\ShipmentRepository;
use Webkul\Sales\Repositories\RefundRepository;

class EmailLogController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(EmailLogDataGrid::class)->process();
        }

        return view('admin::communication.email-logs.index');
    }

    public function resend(Request $request, int $id)
    {
        $log = EmailLog::query()->findOrFail($id);

        try {
            $mailable = $this->buildMailableFromLog($log);

            if (! $mailable) {
                session()->flash('error', 'Unable to reconstruct email for resend.');
                return redirect()->back();
            }

            app(EmailLogRepository::class)->incrementRetry($log);

            Mail::queue($mailable);

            app(EmailLogRepository::class)->update($log, [
                'status' => 'resent',
            ]);

            session()->flash('success', 'Email resent successfully');
        } catch (\Exception $e) {
            app(EmailLogRepository::class)->markFailed($log, $e->getMessage());
            session()->flash('error', $e->getMessage());
        }

        return redirect()->back();
    }

    protected function buildMailableFromLog(EmailLog $log)
    {
        $class = $log->mailable_class;

        switch ($class) {
            case 'Webkul\\Shop\\Mail\\Order\\CreatedNotification':
                $order = app(OrderRepository::class)->find($log->order_id ?? $log->context_id);
                return $order ? new \Webkul\Shop\Mail\Order\CreatedNotification($order) : null;

            case 'Webkul\\Shop\\Mail\\Order\\InvoicedNotification':
                $invoice = app(InvoiceRepository::class)->find($log->context_id);
                return $invoice ? new \Webkul\Shop\Mail\Order\InvoicedNotification($invoice) : null;

            case 'Webkul\\Shop\\Mail\\Order\\ShippedNotification':
                $shipment = app(ShipmentRepository::class)->find($log->context_id);
                return $shipment ? new \Webkul\Shop\Mail\Order\ShippedNotification($shipment) : null;

            case 'Webkul\\Shop\\Mail\\Order\\RefundedNotification':
                $refund = app(RefundRepository::class)->find($log->context_id);
                return $refund ? new \Webkul\Shop\Mail\Order\RefundedNotification($refund) : null;

            case 'Webkul\\Shop\\Mail\\Order\\AbandonedReminder':
                $order = app(OrderRepository::class)->find($log->order_id ?? $log->context_id);
                if ($order) {
                    return new \Webkul\Shop\Mail\Order\AbandonedReminder($order, [
                        'subject' => $log->subject,
                        'body'    => $log->body,
                    ]);
                }
                return null;

            default:
                return null;
        }
    }
}

