<?php

namespace Webkul\Admin\DataGrids\Communication;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class EmailLogDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('email_logs')->select(
            'email_logs.id',
            'email_logs.mailable_class',
            'email_logs.category',
            'email_logs.recipient_email',
            'email_logs.recipient_name',
            'email_logs.subject',
            'email_logs.status',
            'email_logs.retry_count',
            'email_logs.sent_at',
            'email_logs.created_at'
        );

        $this->addFilter('id', 'email_logs.id');
        $this->addFilter('recipient_email', 'email_logs.recipient_email');
        $this->addFilter('subject', 'email_logs.subject');
        $this->addFilter('status', 'email_logs.status');
        $this->addFilter('category', 'email_logs.category');
        $this->addFilter('created_at', 'email_logs.created_at');

        return $queryBuilder;
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('admin::app.communication.email-logs.index.datagrid.id'),
            'type'       => 'integer',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'recipient_email',
            'label'      => trans('admin::app.communication.email-logs.index.datagrid.recipient'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'subject',
            'label'      => trans('admin::app.communication.email-logs.index.datagrid.subject'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'category',
            'label'      => trans('admin::app.communication.email-logs.index.datagrid.category'),
            'type'       => 'string',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'              => 'status',
            'label'              => trans('admin::app.communication.email-logs.index.datagrid.status'),
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
            'label'              => trans('admin::app.communication.email-logs.index.datagrid.created-at'),
            'type'               => 'datetime',
            'filterable'         => true,
            'filterable_type'    => 'datetime_range',
            'sortable'           => true,
        ]);

        $this->addColumn([
            'index'      => 'retry_count',
            'label'      => trans('admin::app.communication.email-logs.index.datagrid.retry-count'),
            'type'       => 'integer',
            'sortable'   => true,
        ]);
    }

    public function prepareActions()
    {
        $this->addAction([
            'index'  => 'resend',
            'icon'   => 'icon-reply',
            'title'  => trans('admin::app.communication.email-logs.index.datagrid.resend'),
            'method' => 'POST',
            'route'  => 'admin.communication.email_logs.resend',
            'url'    => function ($row) {
                return route('admin.communication.email_logs.resend', $row->id);
            },
        ]);
    }
}
