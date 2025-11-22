<x-admin::layouts>
    <x-slot:title>AI Models</x-slot:title>

    <div class="flex items-center justify-between gap-4">
        <p class="text-xl font-bold">AI Models</p>
        <a class="primary-button" href="{{ route('admin.ai_review.models.create') }}">Create</a>
    </div>

    <div class="mt-4">
        <x-admin::datagrid :src="route('admin.ai_review.models.index')" />
    </div>
</x-admin::layouts>