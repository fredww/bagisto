<x-admin::layouts>
    <x-slot:title>
        Email Logs
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Email Logs
        </p>
    </div>

    <x-admin::datagrid src="{{ route('admin.communication.email_logs.index') }}"></x-admin::datagrid>
</x-admin::layouts>

