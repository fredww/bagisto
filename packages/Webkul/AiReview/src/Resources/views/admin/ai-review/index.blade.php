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
        <div class="bg-white rounded-xl p-4 shadow w-1/3" style="width:33% !important">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold">AI Generate</h2>
                <a class="text-blue-600" href="{{ route('admin.ai_review.models.index') }}">Manage Models</a>
            </div>

            <v-ai-review-config>
                <template #default>
                    <div class="mt-4 grid grid-cols-1 gap-4">
                        <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>Gender</x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="select" id="ai_gender">
                                    <option value="any">All</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </x-admin::form.control-group.control>
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>Age Range</x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="text" id="ai_age" placeholder="e.g. 25-40" />
                            </x-admin::form.control-group>
                        </div>

                        <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>Countries/Regions</x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="multiselect" id="ai_regions">
                                    <option value="US">United States</option>
                                    <option value="GB">United Kingdom</option>
                                    <option value="CA">Canada</option>
                                    <option value="DE">Germany</option>
                                    <option value="FR">France</option>
                                    <option value="IT">Italy</option>
                                    <option value="ES">Spain</option>
                                    <option value="NL">Netherlands</option>
                                    <option value="JP">Japan</option>
                                    <option value="KR">South Korea</option>
                                    <option value="CN">China</option>
                                    <option value="HK">Hong Kong</option>
                                    <option value="TW">Taiwan</option>
                                    <option value="SG">Singapore</option>
                                    <option value="IN">India</option>
                                    <option value="AU">Australia</option>
                                    <option value="NZ">New Zealand</option>
                                    <option value="BR">Brazil</option>
                                    <option value="MX">Mexico</option>
                                    <option value="AE">United Arab Emirates</option>
                                </x-admin::form.control-group.control>
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>Languages</x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="multiselect" id="ai_languages">
                                    <option value="en">English</option>
                                    <option value="zh">Chinese</option>
                                    <option value="ja">Japanese</option>
                                    <option value="ko">Korean</option>
                                    <option value="de">German</option>
                                    <option value="fr">French</option>
                                    <option value="es">Spanish</option>
                                    <option value="it">Italian</option>
                                    <option value="pt">Portuguese</option>
                                    <option value="ru">Russian</option>
                                    <option value="ar">Arabic</option>
                                    <option value="hi">Hindi</option>
                                </x-admin::form.control-group.control>
                            </x-admin::form.control-group>
                        </div>

                        <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>Review Date</x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="select" id="ai_date">
                                    <option value="7">Last 7 days</option>
                                    <option value="15">Last 15 days</option>
                                    <option value="30">Last 30 days</option>
                                    <option value="60">Last 60 days</option>
                                </x-admin::form.control-group.control>
                            </x-admin::form.control-group>

                            <div class="grid grid-cols-2 gap-2">
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>From</x-admin::form.control-group.label>
                                    <x-admin::form.control-group.control type="date" id="ai_date_from" />
                                </x-admin::form.control-group>
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>To</x-admin::form.control-group.label>
                                    <x-admin::form.control-group.control type="date" id="ai_date_to" />
                                </x-admin::form.control-group>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>Min per product</x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="number" id="ai_min" value="5" min="1" />
                            </x-admin::form.control-group>
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>Max per product</x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="number" id="ai_max" value="20" min="1" />
                            </x-admin::form.control-group>
                        </div>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>Instruction</x-admin::form.control-group.label>
                            <x-admin::form.control-group.control type="textarea" id="ai_instruction" rows="4" placeholder="Enter generation instruction" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>AI Model</x-admin::form.control-group.label>
                            <x-admin::form.control-group.control type="select" id="ai_model">
                                <option value="">Default</option>
                                @foreach($models as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->provider }})</option>
                                @endforeach
                            </x-admin::form.control-group.control>
                        </x-admin::form.control-group>

                        <div class="flex items-center gap-2">
                            <button type="button" class="primary-button" id="ai_generate_btn">AI Generate</button>
                            <p id="ai_progress" class="text-sm text-gray-600"></p>
                            <a id="ai_export_link" class="hidden text-blue-600" href="#">Export CSV</a>
                        </div>
                    </div>
                </template>
            </v-ai-review-config>
        </div>

        <div class="bg-white rounded-xl p-4 shadow w-2/3" style="width:67% !important">
            <h2 class="text-xl font-semibold mb-3">Select Products</h2>
            <x-admin::datagrid :src="route('admin.ai_review.index')" :isMultiRow="true" />
        </div>
    </div>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-ai-review-config-template">
            <div><slot /></div>
        </script>
        <script type="module">
            app.component('v-ai-review-config', {
                template: '#v-ai-review-config-template',
                data() {
                    return { selectedProductIds: [] };
                },
                mounted() {
                    this.$emitter.on('dxm-change-datagrid', ({ available, applied }) => {
                        this.selectedProductIds = applied.massActions.indices;
                    });
                    const btn = document.getElementById('ai_generate_btn');
                    const progressEl = document.getElementById('ai_progress');
                    const exportLink = document.getElementById('ai_export_link');
                    btn?.addEventListener('click', async () => {
                        progressEl.textContent = '';
                        exportLink.classList.add('hidden');
                        if (!this.selectedProductIds.length) {
                            this.$emitter.emit('add-flash', { type: 'error', message: 'Please select products' });
                            return;
                        }
                        const min = parseInt(document.getElementById('ai_min').value || '5');
                        const max = parseInt(document.getElementById('ai_max').value || '20');
                        if (max < min) {
                            this.$emitter.emit('add-flash', { type: 'error', message: 'Max must be >= Min' });
                            return;
                        }
                        const regionsSelect = document.getElementById('ai_regions');
                        const languagesSelect = document.getElementById('ai_languages');
                        const payload = {
                            product_ids: this.selectedProductIds,
                            min_count: min,
                            max_count: max,
                            gender: document.getElementById('ai_gender').value,
                            age_range: document.getElementById('ai_age').value,
                            regions: Array.from(regionsSelect?.selectedOptions ?? []).map(o => o.value),
                            languages: Array.from(languagesSelect?.selectedOptions ?? []).map(o => o.value),
                            date_range: {
                                preset: document.getElementById('ai_date').value,
                                from: document.getElementById('ai_date_from').value,
                                to: document.getElementById('ai_date_to').value,
                            },
                            model_id: document.getElementById('ai_model').value || null,
                            instruction: document.getElementById('ai_instruction').value,
                        };
                        try {
                            const res = await this.$axios.post("{{ route('admin.ai_review.generate_batch') }}", payload);
                            const jobId = res.data.job_id;
                            progressEl.textContent = `Job #${jobId} started...`;
                            const timer = setInterval(async () => {
                                const st = await this.$axios.get(`/admin/ai-review/jobs/${jobId}`);
                                const d = st.data;
                                progressEl.textContent = `Processed ${d.processed}/${d.total} - ${d.status}`;
                                if (d.status === 'completed') {
                                    clearInterval(timer);
                                    exportLink.href = `/admin/ai-review/jobs/${jobId}/export`;
                                    exportLink.classList.remove('hidden');
                                }
                            }, 1200);
                        } catch (e) {
                            this.$emitter.emit('add-flash', { type: 'error', message: e.response?.data?.message || 'Error' });
                        }
                    });
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>