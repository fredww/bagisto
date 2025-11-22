<?php

namespace Webkul\Admin\DataGrids\Marketing;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class AbandonedOrderTemplateDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('abandoned_order_templates')->select(
            'id', 'name', 'subject', 'trigger_hours', 'active', 'created_at'
        );

        $this->addFilter('id', 'abandoned_order_templates.id');
        $this->addFilter('name', 'abandoned_order_templates.name');
        $this->addFilter('subject', 'abandoned_order_templates.subject');
        $this->addFilter('active', 'abandoned_order_templates.active');

        return $queryBuilder;
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => 'ID',
            'type'       => 'integer',
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => 'Name',
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'subject',
            'label'      => 'Subject',
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'trigger_hours',
            'label'      => 'Trigger Hours',
            'type'       => 'integer',
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'              => 'active',
            'label'              => 'Status',
            'type'               => 'boolean',
            'filterable'         => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => [
                ['label' => 'Active', 'value' => 1],
                ['label' => 'Inactive', 'value' => 0],
            ],
            'sortable'   => true,
        ]);
    }

    public function prepareActions()
    {
        $this->addAction([
            'index'  => 'edit',
            'icon'   => 'icon-edit',
            'title'  => trans('admin::app.common.edit'),
            'method' => 'GET',
            'route'  => 'admin.marketing.abandoned_templates.edit',
            'url'    => function ($row) {
                return route('admin.marketing.abandoned_templates.edit', $row->id);
            },
        ]);

        $this->addMassAction([
            'title'  => trans('admin::app.common.delete'),
            'method' => 'POST',
            'url'    => route('admin.marketing.abandoned_templates.mass_delete'),
            'icon'   => 'icon-delete',
        ]);
    }
}
