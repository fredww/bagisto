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

        <div class="mt-3.5 grid gap-6">
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

            <x-admin::form.control-group>
                <x-admin::form.control-group.label> Body </x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="textarea" name="body" :value="$template->body" rules="required" />
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
        </div>
    </x-admin::form>
</x-admin::layouts>

