<x-admin::layouts>
    <x-slot:title>
        Google Feed
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">Google Feed</p>
    </div>

    <x-admin::form method="POST" :action="route('admin.marketing.google.feed.generate')">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                @if (session('download_url') || session('download_path'))
                    <div class="mb-4">
                        @if (session('download_url'))
                            <a href="{{ session('download_url') }}" target="_blank" class="text-blue-600 hover:underline">Download: {{ session('download_url') }}</a>
                        @else
                            <p class="text-gray-600">Path: {{ session('download_path') }}</p>
                        @endif
                    </div>
                @endif
                <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Channel</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="select" name="channel">
                            <option value="">Default</option>
                            @foreach (core()->getAllChannels() as $channel)
                                <option value="{{ $channel->code }}">{{ $channel->name }}</option>
                            @endforeach
                        </x-admin::form.control-group.control>
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Locale</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="select" name="locale">
                            @foreach (core()->getAllLocales() as $locale)
                                <option value="{{ $locale->code }}">{{ $locale->name }}</option>
                            @endforeach
                        </x-admin::form.control-group.control>
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">Output Path</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" name="output" rules="required" value="public/google-feed.xml" />
                        <x-admin::form.control-group.error control-name="output" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Base URL</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" name="base_url" value="{{ config('app.url') }}" />
                        <x-admin::form.control-group.error control-name="base_url" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Category IDs</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" name="category" placeholder="e.g. 12,34" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Exclude Category IDs</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" name="exclude_category" placeholder="e.g. 56,78" />
                    </x-admin::form.control-group>
                </div>

                <div class="mt-4">
                    <x-admin::button button-type="submit" class="primary-button" title="Generate" />
                </div>
            </div>
    </x-admin::form>
</x-admin::layouts>
