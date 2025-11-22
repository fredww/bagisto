<?php

namespace Webkul\Admin\DataGrids\Reporting;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class AbandonedReminderReportDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('email_logs')
            ->leftJoin('orders', 'email_logs.order_id', '=', 'orders.id')
            ->select(
                'email_logs.id',
                'email_logs.recipient_email',
                'email_logs.subject',
                'email_logs.status',
                'email_logs.created_at',
                'orders.increment_id as order_increment_id',
                'orders.status as order_status',
                DB::raw('IF(orders.status IN ("processing", "completed"), 1, 0) as converted')
            )
            ->where('email_logs.category', 'abandoned_order');

        $this->addFilter('recipient_email', 'email_logs.recipient_email');
        $this->addFilter('subject', 'email_logs.subject');
        $this->addFilter('status', 'email_logs.status');
        $this->addFilter('created_at', 'email_logs.created_at');

        $this->setQueryBuilder($queryBuilder);
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'order_increment_id',
            'label'      => 'Order #',
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'recipient_email',
            'label'      => 'Recipient',
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'subject',
            'label'      => 'Subject',
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'              => 'status',
            'label'              => 'Status',
            'type'               => 'string',
            'filterable'         => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => [
                ['label' => 'Queued', 'value' => 'queued'],
                ['label' => 'Sent', 'value' => 'sent'],
                ['label' => 'Failed', 'value' => 'failed'],
                ['label' => 'Resent', 'value' => 'resent'],
            ],
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'              => 'created_at',
            'label'              => 'Sent At',
            'type'               => 'datetime',
            'filterable'         => true,
            'filterable_type'    => 'datetime_range',
            'sortable'           => true,
        ]);

        $this->addColumn([
            'index'      => 'order_status',
            'label'      => 'Order Status',
            'type'       => 'string',
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'converted',
            'label'      => 'Converted',
            'type'       => 'boolean',
            'sortable'   => true,
        ]);
    }
}

