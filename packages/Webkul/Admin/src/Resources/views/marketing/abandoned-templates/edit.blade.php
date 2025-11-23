<x-admin::layouts>
    <x-slot:title>
        Edit Abandoned Order Template
    </x-slot>

    <x-admin::form :action="route('admin.marketing.abandoned_templates.update', $template->id)">
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">Edit Template</p>

            <div class="flex items-center gap-x-2.5">
                <a href="{{ route('admin.marketing.abandoned_templates.index') }}" class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800">Back</a>

                <button type="submit" class="primary-button">Save</button>
            </div>
        </div>

        <div class="mt-3.5 grid gap-6 md:grid-cols-2">
            <x-admin::form.control-group>
                <x-admin::form.control-group.label> Name </x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="text" name="name" :value="$template->name" rules="required" />
                <x-admin::form.control-group.error control-name="name" />
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label> Subject </x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="text" name="subject" :value="$template->subject" rules="required" />
                <x-admin::form.control-group.error control-name="subject" />
            </x-admin::form.control-group>

            <x-admin::form.control-group class="md:col-span-2">
                <x-admin::form.control-group.label> Body </x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="textarea" name="body" :value="$template->body" rules="required" rows="12" />
                <x-admin::form.control-group.error control-name="body" />
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label> Trigger Hours </x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="number" name="trigger_hours" :value="$template->trigger_hours" rules="required|min:1" />
                <x-admin::form.control-group.error control-name="trigger_hours" />
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label> Active </x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="checkbox" name="active" value="1" :checked="$template->active" />
                <x-admin::form.control-group.error control-name="active" />
            </x-admin::form.control-group>

            <div class="md:col-span-2 grid md:grid-cols-2 gap-6">
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="text-base font-semibold text-gray-800 dark:text-white">Supported Placeholders</p>
                    <div class="mt-3 text-sm text-gray-700 dark:text-gray-300">
                        <ul class="list-disc pl-5 space-y-1">
                            <li>[First Name]</li>
                            <li>[Product items]</li>
                            <li>[support email]</li>
                            <li>[Store Name]</li>
                            <li>[Button: Securely Complete My Order]</li>
                        </ul>
                    </div>
                </div>

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="text-base font-semibold text-gray-800 dark:text-white">Preview</p>
                    <div class="mt-3">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300" id="template-preview-subject"></div>
                        <div class="mt-2 text-sm text-gray-700 dark:text-gray-300" id="template-preview-body"></div>
                    </div>
                </div>
            </div>
        </div>
    </x-admin::form>

    @pushOnce('scripts')
        <script>
            function renderPreview() {
                const subjectInput = document.querySelector('input[name="subject"]');
                const bodyInput = document.querySelector('textarea[name="body"]');
                const subjectEl = document.getElementById('template-preview-subject');
                const bodyEl = document.getElementById('template-preview-body');

                if (!subjectInput || !bodyInput) return;

                const sample = {
                    firstName: 'Alex',
                    supportEmail: 'support@example.com',
                    storeName: 'Demo Store',
                    itemsHtml: '<ul style="padding-left:18px;margin:0"><li>Sample Product × 1 — $29.99</li><li>Another Item × 2 — $59.98</li></ul>',
                    button: '<a href="#" style="display:inline-block;padding:12px 18px;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600">Securely Complete My Order</a>'
                };

                let subj = subjectInput.value || '';
                let body = bodyInput.value || '';

                subj = subj.replaceAll('[First Name]', sample.firstName)
                    .replaceAll('[support email]', sample.supportEmail)
                    .replaceAll('[Store Name]', sample.storeName);

                body = body.replaceAll('[First Name]', sample.firstName)
                    .replaceAll('[support email]', sample.supportEmail)
                    .replaceAll('[Store Name]', sample.storeName)
                    .replaceAll('[Product items]', sample.itemsHtml)
                    .replace(/\[Button:\s*([^\]]+)\]/g, sample.button);

                subjectEl.textContent = subj;
                bodyEl.innerHTML = body;
            }

            document.addEventListener('input', function (e) {
                if (e.target && (e.target.name === 'subject' || e.target.name === 'body')) {
                    renderPreview();
                }
            });

            document.addEventListener('DOMContentLoaded', renderPreview);
        </script>
    @endPushOnce
</x-admin::layouts>
