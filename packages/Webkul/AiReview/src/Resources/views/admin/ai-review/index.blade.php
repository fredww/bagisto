@php
    /**
     * 中文：AI 评测管理页面视图，包含状态、单商品生成表单、以及商品数据表格。
     * Purpose: Admin page for AI Review with status, single generate form, and datagrid.
     */
@endphp

<x-admin::layouts>
    <x-slot:title>
        AI Review
    </x-slot:title>

    <div class="flex gap-4">
        <div class="flex-1">
            <div class="bg-white rounded-xl p-4 shadow">
                <h2 class="text-xl font-semibold mb-3">Status</h2>
                <p>AI Review is enabled.</p>
                <p>Default rating range: 4–5.</p>
            </div>

            <div class="mt-4 bg-white rounded-xl p-4 shadow">
                <h2 class="text-xl font-semibold mb-3">Generate for Single Product</h2>

                <x-admin::form :action="route('admin.ai_review.generate')" method="POST">
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            Product ID
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="product_id"
                            :value="old('product_id')"
                            placeholder="e.g. 42"
                        />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            Count
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="number"
                            name="count"
                            :value="old('count', 1)"
                            min="1"
                            max="5"
                        />
                    </x-admin::form.control-group>

                    <div class="flex gap-2">
                        <x-admin::button type="submit" class="primary">
                            Generate
                        </x-admin::button>
                    </div>
                </x-admin::form>
            </div>
        </div>

        <div class="flex-1">
            <div class="bg-white rounded-xl p-4 shadow">
                <h2 class="text-xl font-semibold mb-3">Quick Actions & Mass Generate</h2>

                <x-admin::datagrid :src="route('admin.ai_review.index')" />
            </div>
        </div>
    </div>
</x-admin::layouts>