<x-admin::layouts>
    <x-slot:title>
        Abandoned Reminder Report
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Abandoned Reminder Report
        </p>
    </div>

    <x-admin::datagrid src="{{ route('admin.reporting.abandoned_reminders.index') }}"></x-admin::datagrid>
</x-admin::layouts>

