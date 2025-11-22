<x-admin::layouts>
    <x-slot:title>Create AI Model</x-slot:title>

    <div class="grid gap-4">
        <form action="{{ route('admin.ai_review.models.store') }}" method="POST">
            @csrf

            <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
                <p class="text-xl font-bold text-gray-800 dark:text-white">Create AI Model</p>

                <div class="flex items-center gap-x-2.5">
                    <a href="{{ route('admin.ai_review.models.index') }}" class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800">Back</a>
                    <button type="submit" class="primary-button">Save</button>
                </div>
            </div>

            <div class="grid gap-4">
                <div>
                    <label class="required block text-gray-800 dark:text-white mb-1">Model ID</label>
                    <input type="text" name="model_id" class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600" required />
                </div>

                <div>
                    <label class="required block text-gray-800 dark:text-white mb-1">Name</label>
                    <input type="text" name="name" class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600" required />
                </div>

                <div>
                    <label class="required block text-gray-800 dark:text-white mb-1">Provider</label>
                    <select name="provider" class="custom-select w-full rounded-md border bg-white px-3 py-2.5 text-sm font-normal text-gray-600" required>
                        <option value="openai">OpenAI</option>
                        <option value="groq">Groq</option>
                        <option value="gemini">Gemini</option>
                        <option value="ollama">Ollama</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-800 dark:text-white mb-1">API Endpoint</label>
                    <input type="text" name="api_endpoint" class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600" placeholder="https://api.openai.com/v1" />
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" id="enabled" name="enabled" value="1" class="rounded border" checked />
                    <label for="enabled">Enabled</label>
                </div>

                <div>
                    <label class="block text-gray-800 dark:text-white mb-1">OpenAI API Key</label>
                    <input type="text" name="auth_api_key" class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600" placeholder="sk-..." />
                </div>

                <div>
                    <label class="block text-gray-800 dark:text-white mb-1">OpenAI Organization ID (optional)</label>
                    <input type="text" name="auth_organization" class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600" placeholder="org_..." />
                </div>

                <div>
                    <label class="block text-gray-800 dark:text-white mb-1">Auth JSON (advanced)</label>
                    <textarea name="auth" rows="3" class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600" placeholder='{"api_key":"sk-...","organization":"org_..."}'></textarea>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="primary-button">Save</button>
                    <a class="secondary-button" href="{{ route('admin.ai_review.models.index') }}">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</x-admin::layouts>