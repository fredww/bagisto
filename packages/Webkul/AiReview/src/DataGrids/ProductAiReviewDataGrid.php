<?php

namespace Webkul\AiReview\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class ProductAiReviewDataGrid extends DataGrid
{
    /**
     * 中文：设置主键列为 product_id。
     * Purpose: Define primary column key for row selection and actions.
     */
    protected $primaryColumn = 'product_id';

    /**
     * 中文：准备查询构建器，查询当前渠道和语言的商品基础信息。
     * Purpose: Build the base query to list products filtered by current channel and locale.
     */
    public function prepareQueryBuilder()
    {
        $channelCode = core()->getCurrentChannelCode();
        $locale = app()->getLocale();

        $tablePrefix = DB::getTablePrefix();

        $queryBuilder = DB::table('product_flat')
            ->leftJoin('product_reviews', 'product_reviews.product_id', '=', 'product_flat.product_id')
            ->select(
                'product_flat.product_id',
                'product_flat.sku',
                'product_flat.name',
                'product_flat.channel',
                'product_flat.locale',
                DB::raw('COUNT('.$tablePrefix.'product_reviews.id) as review_count')
            )
            ->where('product_flat.channel', $channelCode)
            ->where('product_flat.locale', $locale)
            ->groupBy(
                'product_flat.product_id',
                'product_flat.sku',
                'product_flat.name',
                'product_flat.channel',
                'product_flat.locale'
            );

        $this->addFilter('product_id', 'product_flat.product_id');
        $this->addFilter('sku', 'product_flat.sku');
        $this->addFilter('name', 'product_flat.name');
        $this->addFilter('channel', 'product_flat.channel');
        $this->addFilter('locale', 'product_flat.locale');
        $this->addFilter('review_count', DB::raw('COUNT('.$tablePrefix.'product_reviews.id)'));

        return $queryBuilder;
    }

    /**
     * 中文：定义表格列，如商品名称、SKU、ID。
     * Purpose: Configure visible columns for the datagrid.
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('admin::app.catalog.products.index.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'sku',
            'label'      => trans('admin::app.catalog.products.index.datagrid.sku'),
            'type'       => 'string',
            'searchable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'product_id',
            'label'      => trans('admin::app.catalog.products.index.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'review_count',
            'label'      => 'Reviews',
            'type'       => 'aggregate',
            'searchable' => false,
            'sortable'   => true,
        ]);
    }

    /**
     * 中文：为每行添加操作，例如生成 1 条评测。
     * Purpose: Add row-level actions to generate 1 AI review for the product.
     */
    public function prepareActions()
    {
        $this->addAction([
            'method' => 'POST',
            'title'  => trans('admin::app.catalog.products.index.datagrid.copy') . ' AI Review (1)',
            'icon'   => 'icon-magic',
            'url'    => function ($row) {
                return route('admin.ai_review.generate', [
                    'product_id' => $row->product_id,
                    'count'      => 1,
                ]);
            },
        ]);
    }

    /**
     * 中文：添加批量操作，支持选择数量生成多条评测。
     * Purpose: Add mass actions to generate reviews for selected products with chosen count.
     */
    public function prepareMassActions()
    {
        $this->addMassAction([
            'title'   => 'Generate Reviews',
            'method'  => 'POST',
            'url'     => route('admin.ai_review.mass_generate'),
            'options' => [
                [ 'label' => '1', 'value' => '1' ],
                [ 'label' => '3', 'value' => '3' ],
                [ 'label' => '5', 'value' => '5' ],
            ],
        ]);
    }
}