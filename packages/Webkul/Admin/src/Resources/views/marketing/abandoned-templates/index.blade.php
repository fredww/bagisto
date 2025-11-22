<x-admin::layouts>
    <x-slot:title>
        Abandoned Order Templates
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Abandoned Order Templates
        </p>

        <a href="{{ route('admin.marketing.abandoned_templates.create') }}" class="primary-button">Create Template</a>
    </div>

    <x-admin::datagrid src="{{ route('admin.marketing.abandoned_templates.index') }}"></x-admin::datagrid>
</x-admin::layouts>

