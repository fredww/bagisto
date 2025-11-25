<v-wly-export {{ $attributes }}>
    <div class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800">
        <span class="icon-admin-export text-xl text-gray-600"></span>
        万里汇导出
    </div>
</v-wly-export>

@pushOnce('scripts')
    <script type="text/x-template" id="v-wly-export-template">
        <div>
            <x-admin::modal ref="modal">
                <x-slot:toggle>
                    <button class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800">
                        <span class="icon-admin-export text-xl text-gray-600"></span>
                        万里汇导出
                    </button>
                </x-slot:toggle>

                <x-slot:header>
                    <p class="text-lg font-bold text-gray-800 dark:text-white">万里汇导出</p>
                </x-slot:header>

                <x-slot:content>
                    <div class="grid gap-4">
                        <x-admin::form>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        开始日期（可选）
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control type="date" name="start_date" v-model="startDate" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        结束日期（可选）
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control type="date" name="end_date" v-model="endDate" />
                                </x-admin::form.control-group>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">不填日期时导出所有支付成功订单</p>
                        </x-admin::form>

                        <div v-if="progressVisible" class="grid gap-2">
                            <div class="h-2 w-full rounded bg-gray-200 dark:bg-gray-800">
                                <div class="h-2 rounded bg-blue-600" :style="{ width: progressPercent + '%' }"></div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">@{{ processedRows }} / @{{ totalRows }}</p>
                        </div>
                    </div>
                </x-slot:content>

                <x-slot:footer>
                    <x-admin::button button-type="button" class="primary-button" :title="'开始导出'" @click="start" />
                </x-slot:footer>
            </x-admin::modal>
        </div>
    </script>

    <script type="module">
        app.component('v-wly-export', {
            template: '#v-wly-export-template',
            props: ['startUrl', 'statusUrlBase'],
            data() {
                return {
                    startDate: '',
                    endDate: '',
                    taskId: null,
                    progressVisible: false,
                    totalRows: 0,
                    processedRows: 0,
                    downloadUrl: null,
                    timer: null,
                    downloadTriggered: false,
                };
            },
            methods: {
                start() {
                    this.$axios.post(this.startUrl, { start_date: this.startDate || null, end_date: this.endDate || null })
                        .then((response) => {
                            this.taskId = response.data.id;
                            this.progressVisible = true;
                            this.poll();
                        });
                },
                poll() {
                    if (! this.taskId) return;
                    const base = this.statusUrlBase.endsWith('/') ? this.statusUrlBase.slice(0, -1) : this.statusUrlBase;
                    const url = `${base}/${this.taskId}`;
                    this.$axios.get(url)
                        .then((response) => {
                            const data = response.data;
                            this.totalRows = data.totalRows || 0;
                            this.processedRows = data.processedRows || 0;
                            this.downloadUrl = data.downloadUrl;
                            if (data.state === 'completed' && this.downloadUrl) {
                                if (! this.downloadTriggered) {
                                    this.downloadTriggered = true;
                                    window.location.href = this.downloadUrl;
                                }
                                clearTimeout(this.timer);
                            } else {
                                this.timer = setTimeout(this.poll, 1000);
                            }
                        });
                },
            },
            computed: {
                progressPercent() {
                    if (! this.totalRows) return 0;
                    const pct = Math.round((this.processedRows / this.totalRows) * 100);
                    return Math.min(100, pct);
                }
            }
        });
    </script>
@endPushOnce
