<?php

namespace Webkul\Admin\Http\Controllers\Marketing;

use Illuminate\Http\Request;
use Webkul\Admin\DataGrids\Marketing\AbandonedOrderTemplateDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Marketing\Models\AbandonedOrderTemplate;
use Webkul\Marketing\Repositories\AbandonedOrderTemplateRepository;

class AbandonedOrderTemplateController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(AbandonedOrderTemplateDataGrid::class)->process();
        }

        return view('admin::marketing.abandoned-templates.index');
    }

    public function create()
    {
        return view('admin::marketing.abandoned-templates.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string',
            'subject'       => 'required|string',
            'body'          => 'required|string',
            'active'        => 'nullable|boolean',
            'trigger_hours' => 'required|integer|min:1',
        ]);

        app(AbandonedOrderTemplateRepository::class)->create($data);

        session()->flash('success', 'Template created');

        return redirect()->route('admin.marketing.abandoned_templates.index');
    }

    public function edit(int $id)
    {
        $template = AbandonedOrderTemplate::query()->findOrFail($id);
        return view('admin::marketing.abandoned-templates.edit', compact('template'));
    }

    public function update(Request $request, int $id)
    {
        $template = AbandonedOrderTemplate::query()->findOrFail($id);

        $data = $request->validate([
            'name'          => 'required|string',
            'subject'       => 'required|string',
            'body'          => 'required|string',
            'active'        => 'nullable|boolean',
            'trigger_hours' => 'required|integer|min:1',
        ]);

        app(AbandonedOrderTemplateRepository::class)->update($template, $data);

        session()->flash('success', 'Template updated');

        return redirect()->route('admin.marketing.abandoned_templates.index');
    }

    public function massDelete(Request $request)
    {
        $ids = $request->input('indices', []);
        AbandonedOrderTemplate::query()->whereIn('id', $ids)->delete();

        return response()->json(['message' => 'Deleted'], 200);
    }
}

