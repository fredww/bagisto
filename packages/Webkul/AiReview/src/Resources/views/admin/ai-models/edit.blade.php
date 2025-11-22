<x-admin::layouts>
    <x-slot:title>Edit AI Model</x-slot:title>

    <div class="grid gap-4">
        <x-admin::form :action="route('admin.ai_review.models.update', $model->id)" method="PUT">
            <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
                <p class="text-xl font-bold text-gray-800 dark:text-white">Edit AI Model</p>

                <div class="flex items-center gap-x-2.5">
                    <a href="{{ route('admin.ai_review.models.index') }}" class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800">Back</a>
                    <button type="submit" class="primary-button">Save</button>
                </div>
            </div>
            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">Model ID</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="text" name="model_id" value="{{ $model->model_id }}" rules="required" :label="trans('admin::app.common.name')" :placeholder="trans('admin::app.common.name')" />
                <x-admin::form.control-group.error control-name="model_id" />
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">Name</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="text" name="name" value="{{ $model->name }}" rules="required" :label="trans('admin::app.common.name')" :placeholder="trans('admin::app.common.name')" />
                <x-admin::form.control-group.error control-name="name" />
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">Provider</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="select" name="provider" rules="required">
                    <option value="openai" @selected($model->provider === 'openai')>OpenAI</option>
                    <option value="groq" @selected($model->provider === 'groq')>Groq</option>
                    <option value="gemini" @selected($model->provider === 'gemini')>Gemini</option>
                    <option value="ollama" @selected($model->provider === 'ollama')>Ollama</option>
                </x-admin::form.control-group.control>
                <x-admin::form.control-group.error control-name="provider" />
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label>API Endpoint</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="text" name="api_endpoint" value="{{ $model->api_endpoint }}" :label="'API Endpoint'" :placeholder="'https://api.openai.com/v1'" />
                <x-admin::form.control-group.error control-name="api_endpoint" />
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label>Enabled</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="checkbox" id="enabled" name="enabled" value="1" for="enabled" @checked($model->enabled) />
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label>OpenAI API Key</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="text" name="auth_api_key" value="{{ is_array($model->auth) ? ($model->auth['api_key'] ?? '') : '' }}" :label="'OpenAI API Key'" :placeholder="'sk-...'" />
                <x-admin::form.control-group.error control-name="auth_api_key" />
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label>OpenAI Organization ID (optional)</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="text" name="auth_organization" value="{{ is_array($model->auth) ? ($model->auth['organization'] ?? '') : '' }}" :label="'OpenAI Organization ID'" :placeholder="'org_...'" />
                <x-admin::form.control-group.error control-name="auth_organization" />
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label>Auth JSON (advanced)</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="textarea" name="auth" rows="3" :label="'Auth JSON'" :placeholder="'{\"api_key\":\"sk-...\",\"organization\":\"org_...\"}'">{{ json_encode($model->auth) }}</x-admin::form.control-group.control>
                <x-admin::form.control-group.error control-name="auth" />
            </x-admin::form.control-group>

            <div class="flex gap-2">
                <button type="submit" class="primary-button">Save</button>
                <a class="secondary-button" href="{{ route('admin.ai_review.models.index') }}">Cancel</a>
            </div>
        </x-admin::form>
    </div>
</x-admin::layouts>