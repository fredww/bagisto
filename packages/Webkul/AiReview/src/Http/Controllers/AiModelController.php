<?php

namespace Webkul\AiReview\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\AiReview\DataGrids\AiModelDataGrid;
use Webkul\AiReview\Models\AiModel;

class AiModelController extends Controller
{
    public function index(): View|\Illuminate\Http\JsonResponse
    {
        if (request()->ajax()) {
            return datagrid(AiModelDataGrid::class)->process();
        }

        return view('ai_review::admin.ai-models.index');
    }

    public function create(): View
    {
        return view('ai_review::admin.ai-models.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validate($request, [
            'model_id'    => 'required|string|max:255|unique:ai_models,model_id',
            'name'        => 'required|string|max:255',
            'provider'    => 'required|string|max:255',
            'api_endpoint'=> 'nullable|string|max:1000',
            'enabled'     => 'nullable|boolean',
            'auth'        => 'nullable',
            'auth_api_key'=> 'nullable|string',
            'auth_organization' => 'nullable|string',
        ]);

        $data['enabled'] = (bool) ($data['enabled'] ?? true);
        $auth = $request->input('auth') ? json_decode($request->input('auth'), true) : [];
        if ($request->filled('auth_api_key')) {
            $auth['api_key'] = $request->input('auth_api_key');
        }
        if ($request->filled('auth_organization')) {
            $auth['organization'] = $request->input('auth_organization');
        }
        $data['auth'] = empty($auth) ? null : $auth;

        AiModel::create($data);

        session()->flash('success', trans('admin::app.response.create-success', ['name' => 'AI Model']));

        return redirect()->route('admin.ai_review.models.index');
    }

    public function edit(int $id): View
    {
        $model = AiModel::findOrFail($id);

        return view('ai_review::admin.ai-models.edit', compact('model'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $model = AiModel::findOrFail($id);

        $data = $this->validate($request, [
            'model_id'    => 'required|string|max:255|unique:ai_models,model_id,'.$model->id,
            'name'        => 'required|string|max:255',
            'provider'    => 'required|string|max:255',
            'api_endpoint'=> 'nullable|string|max:1000',
            'enabled'     => 'nullable|boolean',
            'auth'        => 'nullable',
            'auth_api_key'=> 'nullable|string',
            'auth_organization' => 'nullable|string',
        ]);

        $data['enabled'] = (bool) ($data['enabled'] ?? true);
        $auth = $request->input('auth') ? json_decode($request->input('auth'), true) : [];
        if ($request->filled('auth_api_key')) {
            $auth['api_key'] = $request->input('auth_api_key');
        }
        if ($request->filled('auth_organization')) {
            $auth['organization'] = $request->input('auth_organization');
        }
        $data['auth'] = empty($auth) ? null : $auth;

        $model->update($data);

        session()->flash('success', trans('admin::app.response.update-success', ['name' => 'AI Model']));

        return redirect()->route('admin.ai_review.models.index');
    }

    public function destroy(int $id): \Illuminate\Http\JsonResponse
    {
        $model = AiModel::findOrFail($id);
        $model->delete();

        return response()->json([
            'message' => trans('admin::app.response.delete-success', ['name' => 'AI Model'])
        ]);
    }
}